from flask import Flask, jsonify, request
import mysql.connector
import os

app = Flask(__name__)

@app.route('/')
def index():
    with open('index.html', 'r', encoding='utf-8') as file:
        return file.read()

@app.route('/get-questions')
def get_questions():
    try:
        subject = request.args.get('subject')
        education_level = request.args.get('education_level')
        
        conn = mysql.connector.connect(
            host="https://dbwebsitepi-dennisluisky228-a11d.d.aivencloud.com", 
            user="avnadmin",         
            password="AVNS_EAXcIoeolpK4AaywvrL",         
            database="testwebsite"  
        )
        cursor = conn.cursor(dictionary=True)
        

        where_conditions = []
        params = []
        
        if subject:
            where_conditions.append("subject = %s")
            params.append(subject)
        
        if education_level:
            where_conditions.append("education_level = %s")
            params.append(education_level)
        
        where_clause = ""
        if where_conditions:
            where_clause = "WHERE " + " AND ".join(where_conditions)
        
        query = f"""
            SELECT question_text, option_a, option_b, option_c, option_d, correct_answer
            FROM questions
            {where_clause}
            ORDER BY RAND()
            LIMIT 20
        """
        
        cursor.execute(query, params)
        rows = cursor.fetchall()
        conn.close()

        questions = []
        for row in rows:
            letter_to_number = {'A': 0, 'B': 1, 'C': 2, 'D': 3}
            correct_answer_index = letter_to_number.get(row["correct_answer"].upper(), 0)
            
            questions.append({
                "question": row["question_text"],
                "options": [row["option_a"], row["option_b"], row["option_c"], row["option_d"]],
                "correct": correct_answer_index  
            })

        return jsonify(questions)
    except mysql.connector.Error as err:
        return jsonify({"error": str(err)}), 500

@app.route('/favicon.ico')
def favicon():
    return '', 204

if __name__ == '__main__':
    print("🚀 Starting Exam Website...")
    print("📱 Visit: http://localhost:5000")
    app.run(debug=True, host='0.0.0.0', port=5000)
