from flask import Flask, render_template, request, jsonify
from flask_cors import CORS

import pymysql.cursors

app = Flask(__name__)
CORS(app)

# MySQL configurations
db_config = {
    'host': 'localhost',
    'user': 'root',
    'password': 'nitin',
    'database': 'weblog',
    'cursorclass': pymysql.cursors.DictCursor,
}

# Function to validate token from the database
def is_valid_token(token):
    connection = pymysql.connect(**db_config)
    try:
        with connection.cursor() as cursor:
            sql = "SELECT * FROM users WHERE token=%s"
            cursor.execute(sql, (token,))
            result = cursor.fetchone()
            return result is not None
    finally:
        connection.close()

# Function to fetch user details based on Aadhaar number
def fetch_user_details(aadhaar):
    connection = pymysql.connect(**db_config)
    try:
        with connection.cursor() as cursor:
            sql = "SELECT name, fatherName, institutename FROM users WHERE aadhaar=%s"
            cursor.execute(sql, (aadhaar,))
            result = cursor.fetchone()
            return result
    finally:
        connection.close()

# Function to store form data in the database only if the token is valid
def store_form_data(data):
    connection = pymysql.connect(**db_config)
    try:
        with connection.cursor() as cursor:
            # Check if the token exists in the database
            sql_check_token = "SELECT * FROM users WHERE token=%s"
            cursor.execute(sql_check_token, (data['token'],))
            result = cursor.fetchone()

            if result:
                # Token exists, update the user's data
                sql_update_data = "UPDATE users SET name=%s, fatherName=%s, email=%s, aadhaar=%s, phone=%s, address=%s, pincode=%s, institutename=%s, gender=%s, dob=%s, course=%s, classYear=%s, photo=%s, signature=%s WHERE token=%s"
                cursor.execute(sql_update_data, (data['name'], data['fatherName'], data['email'], data['aadhaar'], data['phone'], data['address'], data['pincode'], data['institutename'],  data['gender'], data['dob'], data['course'], data['classYear'], data['photo'], data['signature'], data['token']))

            else:
                # Token does not exist, insert a new record
                sql_insert_data = "INSERT INTO users (name, fatherName, aadhaar, phone, address, pincode, institutename, gender, dob, course, classYear, photo, signature, token) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)"
                cursor.execute(sql_insert_data, (data['name'], data['fatherName'], data['aadhaar'], data['phone'], data['address'], data['pincode'], data['institutename'], data['gender'], data['dob'], data['course'], data['classYear'], data['photo'], data['signature'], data['token']))

        connection.commit()
    finally:
        connection.close()

# Route to fetch user details based on Aadhaar number
@app.route('/fetch-user-details', methods=['GET'])
def fetch_user_details_route():
    aadhaar = request.args.get('aadhaar')

    user_details = fetch_user_details(aadhaar)

    if user_details:
        return jsonify(user_details)
    else:
        return jsonify({'error': 'User not found'})

@app.route('/')
def index():
    return render_template('index.html')

@app.route('/submit', methods=['POST'])
def submit():
    if request.method == 'POST':
        # Get form data
        data = {
            'name': request.form['name'],
            'fatherName': request.form['fatherName'],
            'email': request.form['email'],
            'aadhaar': request.form['aadhaar'],
            'phone':request.form['phone'],
            'address':request.form['address'],
            'pincode' :request.form['pincode'],
            'institutename':request.form['institutename'],
            'gender': request.form['gender'],
            'dob': request.form['dob'],
            'course': request.form['course'],
            'classYear': request.form['classYear'],
            'photo': request.form['photo'],
            'signature': request.form['signature'],
            'token': request.form['token'],
        }

        # Fetch user details based on Aadhaar number
        user_details = fetch_user_details(data['aadhaar'])

        # If user details are found, update the form data
        if user_details:
            data['name'] = user_details['name']
            data['fatherName'] = user_details['fatherName']
            data['institutename'] = user_details['institutename']

        # Validate the token and store form data in the database
        if is_valid_token(data['token']):
            store_form_data(data)
            return "Form submitted successfully!"
        else:
            return "Invalid token. Form submission canceled."

if __name__ == '__main__':
    app.run(debug=True)
