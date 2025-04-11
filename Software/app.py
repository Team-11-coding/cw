import logging
import random
from model import models_line4, models_line5, process_predictions
from flask import Flask, jsonify, request, send_from_directory
from flask_cors import CORS
from threading import Thread
from flask_sqlalchemy import SQLAlchemy
from datetime import datetime
import time
import csv
import logging 

app = Flask(__name__)
CORS(app, supports_credentials=True)

logging.basicConfig(level=logging.DEBUG)
#  Configure Database
app.config['SQLALCHEMY_DATABASE_URI'] = "sqlite:///data.db"   
app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False  

app.config['SQLALCHEMY_ENGINE_OPTIONS'] = {
    'pool_size': 10,   
    'pool_timeout': 30,
    'pool_recycle': 3600,
    'connect_args': {'check_same_thread': False}   
}



db = SQLAlchemy(app)

#  Database Models
class Line4Data(db.Model):
    __tablename__ = 'line4_data'
    id = db.Column(db.Integer, primary_key=True)
    timestamp = db.Column(db.DateTime, nullable=False)
    r01 = db.Column(db.Integer, nullable=False)
    r02 = db.Column(db.Integer, nullable=False)
    r03 = db.Column(db.Integer, nullable=False)
    r04 = db.Column(db.Integer, nullable=False)
    r05 = db.Column(db.Integer, nullable=False)
    r06 = db.Column(db.Integer, nullable=False)
    r07 = db.Column(db.Integer, nullable=False)
    r08 = db.Column(db.Integer, nullable=False)

    def to_dict(self):
        return {
            "id": self.id,
            "timestamp": self.timestamp,
            "r01": self.r01,
            "r02": self.r02,
            "r03": self.r03,
            "r04": self.r04,
            "r05": self.r05,
            "r06": self.r06,
            "r07": self.r07,
            "r08": self.r08
        }

class Line5Data(db.Model):
    __tablename__ = 'line5_data'
    id = db.Column(db.Integer, primary_key=True)
    timestamp = db.Column(db.DateTime, nullable=False)
    r01 = db.Column(db.Integer, nullable=False)
    r02 = db.Column(db.Integer, nullable=False)
    r03 = db.Column(db.Integer, nullable=False)
    r04 = db.Column(db.Integer, nullable=False)
    r05 = db.Column(db.Integer, nullable=False)
    r06 = db.Column(db.Integer, nullable=False)
    r07 = db.Column(db.Integer, nullable=False)
    r08 = db.Column(db.Integer, nullable=False)
    r09 = db.Column(db.Integer, nullable=False)
    r10 = db.Column(db.Integer, nullable=False)
    r11 = db.Column(db.Integer, nullable=False)
    r12 = db.Column(db.Integer, nullable=False)
    r13 = db.Column(db.Integer, nullable=False)
    r14 = db.Column(db.Integer, nullable=False)
    r15 = db.Column(db.Integer, nullable=False)
    r16 = db.Column(db.Integer, nullable=False)
    r17 = db.Column(db.Integer, nullable=False)

    def to_dict(self):
        return {
            "id": self.id,
            "timestamp": self.timestamp,
            "r01": self.r01,
            "r02": self.r02,
            "r03": self.r03,
            "r04": self.r04,
            "r05": self.r05,
            "r06": self.r06,
            "r07": self.r07,
            "r08": self.r08,
            "r09": self.r09,
            "r10": self.r10,
            "r11": self.r11,
            "r12": self.r12,
            "r13": self.r13,
            "r14": self.r14,
            "r15": self.r15,
            "r16": self.r16,
            "r17": self.r17
        }

#   Create Tables
def create_tables():
    with app.app_context():  
        db.create_all()


#  Save Data to Database (Batch Insert)
def save_to_db_batch(data, line_type):
    entries = []
    for row in data:
        if line_type == "line4":
            entries.append(Line4Data(
                timestamp=row['timestamp'],
                r01=row['r01'], r02=row['r02'], r03=row['r03'],
                r04=row['r04'], r05=row['r05'], r06=row['r06'],
                r07=row['r07'], r08=row['r08']
            ))
        elif line_type == "line5":
            entries.append(Line5Data(
                timestamp=row['timestamp'],
                r01=row['r01'], r02=row['r02'], r03=row['r03'],
                r04=row['r04'], r05=row['r05'], r06=row['r06'],
                r07=row['r07'], r08=row['r08'], r09=row['r09'],
                r10=row['r10'], r11=row['r11'], r12=row['r12'],
                r13=row['r13'], r14=row['r14'], r15=row['r15'],
                r16=row['r16'], r17=row['r17']
            ))

    retry_count = 3  
    for attempt in range(retry_count):
        try:
            db.session.add_all(entries)  
            db.session.commit()  # Commit the session
            break  # Exit the loop if commit is successful
        except Exception as e:
            if "database is locked" in str(e).lower():  # Check if error is due to database lock
                logging.warning(f"Database locked, retrying... ({attempt+1}/{retry_count})")
                time.sleep(1)   
            else:
                raise e  # Raise the exception if it's a different error
    else:
        logging.error("Failed to commit after retries. Database is locked.") 
        
#  Import CSV to DB with Batch Insert
def import_csv_to_db():
    line4_data = []
    with open('data/line4.csv', 'r') as line4_file:
        reader = csv.DictReader(line4_file)
        for row in reader:
            timestamp_str = row['timestamp']
            if '+' in timestamp_str:
                timestamp_str = timestamp_str.split('+')[0]  # Remove the timezone part
            
            line4_data.append({
                'timestamp': datetime.strptime(timestamp_str, '%Y-%m-%d %H:%M:%S.%f'),  # Updated format without timezone
                'r01': int(row['r01']),
                'r02': int(row['r02']),
                'r03': int(row['r03']),
                'r04': int(row['r04']),
                'r05': int(row['r05']),
                'r06': int(row['r06']),
                'r07': int(row['r07']),
                'r08': int(row['r08'])
            })
    
    # Batch insert for line4
    save_to_db_batch(line4_data, "line4")
    
    line5_data = []
    with open('data/line5.csv', 'r') as line5_file:
        reader = csv.DictReader(line5_file)
        for row in reader:
            timestamp_str = row['timestamp']
            if '+' in timestamp_str:
                timestamp_str = timestamp_str.split('+')[0]  # Remove the timezone part
            
            line5_data.append({
                'timestamp': datetime.strptime(timestamp_str, '%Y-%m-%d %H:%M:%S.%f'),  
                'r01': int(row['r01']),
                'r02': int(row['r02']),
                'r03': int(row['r03']),
                'r04': int(row['r04']),
                'r05': int(row['r05']),
                'r06': int(row['r06']),
                'r07': int(row['r07']),
                'r08': int(row['r08']),
                'r09': int(row['r09']),
                'r10': int(row['r10']),
                'r11': int(row['r11']),
                'r12': int(row['r12']),
                'r13': int(row['r13']),
                'r14': int(row['r14']),
                'r15': int(row['r15']),
                'r16': int(row['r16']),
                'r17': int(row['r17'])
            })
    
     
    save_to_db_batch(line5_data, "line5")
    
def read_from_db(line_type, limit, offset, date_filter=None):
    try:
        if line_type == "line4":
            query = Line4Data.query
            if date_filter:
                date_obj = datetime.strptime(date_filter, '%Y-%m-%d')
                query = query.filter(db.func.date(Line4Data.timestamp) == date_obj.date())
        elif line_type == "line5":
            query = Line5Data.query
            if date_filter:
                date_obj = datetime.strptime(date_filter, '%Y-%m-%d')
                query = query.filter(db.func.date(Line5Data.timestamp) == date_obj.date())

        query = query.offset(offset).limit(limit)
        data = [entry.to_dict() for entry in query.all()]
        logging.debug(f"Data returned for {line_type}: {data}")
        return data

    except Exception as e:
        logging.error(f"Error in read_from_db: {str(e)}")
        return [], None


# Routes
@app.route('/api/data', methods=['GET'])
def get_data():
    page = int(request.args.get('page', 1))
    limit = int(request.args.get('limit', 500))
    offset = (page - 1) * limit
    date_filter = request.args.get('date')

    line4_data = read_from_db("line4", limit, offset, date_filter)
    line5_data = read_from_db("line5", limit, offset, date_filter)

    has_next = len(line4_data) == limit or len(line5_data) == limit

    return jsonify({
        "line4": line4_data,
        "line5": line5_data,
        "pagination": {
            "current_page": page,
            "next_page": page + 1 if has_next else None,
            "prev_page": page - 1 if page > 1 else None
        }
    })

@app.route('/api/random_data', methods=['GET'])
def get_random_data():
    random_line4 = Line4Data.query.order_by(db.func.random()).first()
    random_line5 = Line5Data.query.order_by(db.func.random()).first()

    if random_line4 is None or random_line5 is None:
        return jsonify({"error": "Random data is not yet available."}), 404

    current_timestamp = datetime.now()

    random_line4_dict = random_line4.to_dict()
    random_line5_dict = random_line5.to_dict()

    random_line4_dict["timestamp"] = current_timestamp
    random_line5_dict["timestamp"] = current_timestamp

    # Insert new records into DB
    new_line4_entry = Line4Data(
        timestamp=current_timestamp,
        r01=random_line4.r01, r02=random_line4.r02, r03=random_line4.r03,
        r04=random_line4.r04, r05=random_line4.r05, r06=random_line4.r06,
        r07=random_line4.r07, r08=random_line4.r08
    )

    new_line5_entry = Line5Data(
        timestamp=current_timestamp,
        r01=random_line5.r01, r02=random_line5.r02, r03=random_line5.r03,
        r04=random_line5.r04, r05=random_line5.r05, r06=random_line5.r06,
        r07=random_line5.r07, r08=random_line5.r08, r09=random_line5.r09,
        r10=random_line5.r10, r11=random_line5.r11, r12=random_line5.r12,
        r13=random_line5.r13, r14=random_line5.r14, r15=random_line5.r15,
        r16=random_line5.r16, r17=random_line5.r17
    )

    db.session.add(new_line4_entry)
    db.session.add(new_line5_entry)
    db.session.commit()

    # Run predictions here
    from model import process_predictions, models_line4, models_line5
    line4_statuses, line5_statuses = process_predictions(
        [random_line4_dict], models_line4, models_line5
    )

    return jsonify({
        "line4": random_line4_dict,
        "line5": random_line5_dict,
        "predictionsLine4": line4_statuses,
        "predictionsLine5": line5_statuses
    })

#  Run the CSV import in a separate thread to avoid blocking the app startup
def run_import():
    with app.app_context():
        import_csv_to_db()
        
@app.route('/')
def serve_html():
    return send_from_directory('web', 'dashB.html')

@app.route('/<path:filename>')
def serve_static(filename):
    return send_from_directory('web', filename)
@app.after_request
def add_cors_headers(response):
    response.headers['Access-Control-Allow-Origin'] = '*'
    response.headers['Access-Control-Allow-Headers'] = 'Content-Type,Authorization'
    response.headers['Access-Control-Allow-Methods'] = 'GET,POST,OPTIONS'
    return response



if __name__ == '__main__':
    create_tables()   
    # Start the CSV import in a separate thread
    import_thread = Thread(target=run_import)
    import_thread.start()
    app.run(debug=True)
