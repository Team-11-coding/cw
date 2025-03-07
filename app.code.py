import os
import pandas as pd
import joblib
import mysql.connector
import config  
from flask import Flask, request, jsonify

app = Flask(__name__)

 
def load_models(models_dirs):
    models = {}
    for models_dir in models_dirs:
        for model_file in os.listdir(models_dir):
            if model_file.endswith('.pkl'):  
                model_name = model_file.split('.')[0]  
                model_path = os.path.join(models_dir, model_file)
                with open(model_path, 'rb') as f:
                    models[model_name] = joblib.load(f)   
    return models

# Load models from both line-4 and line-5 directories
models_dirs = ['machine-learning/models/line4', 'machine-learning/models/line5']
models = load_models(models_dirs)

def calculate_traffic_light(expected_value, lower_bound, upper_bound):
    if expected_value < lower_bound:
        return "Red"
    elif expected_value > upper_bound:
        return "Red"
    elif expected_value >= lower_bound and expected_value <= upper_bound:
        return "Green"
    elif (expected_value >= lower_bound * 1.05) or (expected_value <= upper_bound * 0.95):
        return "Amber"
    return "Green"

 def create_db_connection():
    db_config = config.db_config  # Get database configuration from config.py
    conn = mysql.connector.connect(**db_config)
    return conn

 def fetch_historical_data():
    conn = create_db_connection()
    cursor = conn.cursor()

     cursor.execute("SELECT * FROM line4_data")   
    line4_data = cursor.fetchall()

    # Query for Line 5 data  
    cursor.execute("SELECT * FROM line5_data")    
    line5_data = cursor.fetchall()

    conn.close()

    return line4_data, line5_data

# Function to process data and make predictions
def process_data_and_predict():
    line4_data, line5_data = fetch_historical_data()

    # Process Line 4 data
    for row in line4_data:
        sensor_id = row[0]  
        input_data = pd.DataFrame([row[2:]])   
        model_name = f'prophet_{row[1]}'  # Assuming 'r01', 'r02', etc. are in row[1]

        if model_name in models:
            model = models[model_name]
            expected_value = model.predict(input_data)[0]

            lower_bound = expected_value * 0.90
            upper_bound = expected_value * 1.10
            traffic_light = calculate_traffic_light(expected_value, lower_bound, upper_bound)

            print(f"Sensor {sensor_id} prediction: {expected_value}, Traffic Light: {traffic_light}")

    # Process Line 5 data  
    for row in line5_data:
        sensor_id = row[0]  
        input_data = pd.DataFrame([row[2:]])   
        model_name = f'prophet_{row[1]}'   

        if model_name in models:
            model = models[model_name]
            expected_value = model.predict(input_data)[0]

            lower_bound = expected_value * 0.90
            upper_bound = expected_value * 1.10
            traffic_light = calculate_traffic_light(expected_value, lower_bound, upper_bound)

            print(f"Sensor {sensor_id} prediction: {expected_value}, Traffic Light: {traffic_light}")

# API endpoint for predictions
@app.route('/predict', methods=['POST'])
def predict():
    try:
        data = request.get_json()
        model_name = data['model_name']
        input_data = pd.DataFrame(data['input_features'], index=[0])

        if model_name not in models:
            return jsonify({'error': f'Model "{model_name}" not found.'}), 400

        model = models[model_name]
        expected_value = model.predict(input_data)[0]

        lower_bound = expected_value * 0.90
        upper_bound = expected_value * 1.10
        traffic_light = calculate_traffic_light(expected_value, lower_bound, upper_bound)

        return jsonify({
            'expected_value': expected_value,
            'lower_bound': lower_bound,
            'upper_bound': upper_bound,
            'traffic_light': traffic_light
        })

    except Exception as e:
        return jsonify({'error': str(e)}), 400

def process_historical_data():
    line4_data, line5_data = fetch_historical_data()

    for row in line4_data:
        sensor_id = row[0]   
        input_data = pd.DataFrame([row[2:]])  
        model_name = f'prophet_{row[1]}'

        if model_name in models:
            model = models[model_name]
            expected_value = model.predict(input_data)[0]

            lower_bound = expected_value * 0.90
            upper_bound = expected_value * 1.10
            traffic_light = calculate_traffic_light(expected_value, lower_bound, upper_bound)

            print(f"Sensor {sensor_id} prediction: {expected_value}, Traffic Light: {traffic_light}")

    for row in line5_data:
        sensor_id = row[0]  
        input_data = pd.DataFrame([row[2:]])    
        model_name = f'prophet_{row[1]}'

        if model_name in models:
            model = models[model_name]
            expected_value = model.predict(input_data)[0]

            lower_bound = expected_value * 0.90
            upper_bound = expected_value * 1.10
            traffic_light = calculate_traffic_light(expected_value, lower_bound, upper_bound)

            print(f"Sensor {sensor_id} prediction: {expected_value}, Traffic Light: {traffic_light}")

if __name__ == '__main__':
    process_historical_data()
    app.run(debug=True)
