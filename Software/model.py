import joblib
import os
import pandas as pd
import numpy as np

line4_model_path = 'machine-learning/models/line4/'
line5_model_path = os.path.abspath('machine-learning/models/line5/')

models_line4 = []
models_line5 = []
models_loaded = False

def load_models():
    global models_line4, models_line5, models_loaded

    if models_loaded:
        return models_line4, models_line5

    models_line4 = []
    models_line5 = []

    # Load Line 4 models
    for i in range(1, 9):
        model_path = os.path.join(line4_model_path, f'prophet_r{i:02d}.pkl')
        if os.path.exists(model_path):
            try:
                models_line4.append(joblib.load(model_path))
                print(f"Loaded Line 4 model: {model_path}")
            except Exception as e:
                print(f" Error loading Line 4 model {model_path}: {e}")
                models_line4.append(None)
        else:
            print(f"⚠️ Line 4 model not found: {model_path}")
            models_line4.append(None)

    # Load Line 5 models
    for i in range(1, 18):
        model_path = os.path.join(line5_model_path, f'prophet_r{i:02d}.pkl')
        if os.path.exists(model_path):
            try:
                models_line5.append(joblib.load(model_path))
                print(f"Loaded Line 5 model: {model_path}")
            except Exception as e:
                print(f"Error loading Line 5 model {model_path}: {e}")
                models_line5.append(None)
        else:
            print(f" Line 5 model not found: {model_path}")
            models_line5.append(None)

    models_loaded = True
    return models_line4, models_line5

models_line4, models_line5 = load_models()

def to_serializable(obj):
    if isinstance(obj, (np.integer, np.int64, int)):
        return int(obj)
    elif isinstance(obj, (np.floating, np.float64, float)):
        return float(obj)
    elif isinstance(obj, np.ndarray):
        return obj.tolist()
    return obj

def forecast_with_prophet(model, timestamp, actual_value):
    df = pd.DataFrame({'ds': pd.to_datetime([timestamp])})
    forecast = model.predict(df)

    yhat = forecast['yhat'].values[0]
    yhat_lower = forecast['yhat_lower'].values[0]
    yhat_upper = forecast['yhat_upper'].values[0]

    # Threshold-based logic: ±15 beyond bounds = red
    margin = 15
    if actual_value > yhat_upper + margin or actual_value < yhat_lower - margin:
        status = "red"
    elif actual_value > yhat_upper or actual_value < yhat_lower:
        status = "amber"
    else:
        status = "green"

    return {
        "status": status,
        "expected": to_serializable(round(yhat, 2)),
        "actual": to_serializable(actual_value),
        "range": [
            to_serializable(round(yhat_lower, 2)),
            to_serializable(round(yhat_upper, 2))
        ]
    }


def process_predictions(data, models_line4, models_line5):
    df = pd.DataFrame(data)
    timestamp = df['timestamp'].values[0]

    line4_status = []
    for i, model in enumerate(models_line4):
        sensor = f"r{i+1:02d}"
        if model and sensor in df:
            try:
                result = forecast_with_prophet(model, timestamp, df[sensor].values[0])
                line4_status.append(result)
            except Exception as e:
                print(f"Line 4 {sensor} prediction error: {e}")
                line4_status.append({
                    "status": "amber", "expected": None,
                    "actual": None, "range": [None, None]
                })
        else:
            line4_status.append({
                "status": "amber", "expected": None,
                "actual": None, "range": [None, None]
            })

    line5_status = []
    for i, model in enumerate(models_line5):
        sensor = f"r{i+1:02d}"
        if model and sensor in df:
            try:
                result = forecast_with_prophet(model, timestamp, df[sensor].values[0])
                line5_status.append(result)
            except Exception as e:
                print(f"Line 5 {sensor} prediction error: {e}")
                line5_status.append({
                    "status": "amber", "expected": None,
                    "actual": None, "range": [None, None]
                })
        else:
            line5_status.append({
                "status": "amber", "expected": None,
                "actual": None, "range": [None, None]
            })

    return line4_status, line5_status
