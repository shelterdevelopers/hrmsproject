<?php 
session_start();
if (isset($_SESSION['role']) && isset($_SESSION['employee_id'])) {
    include "../DB_connection.php";
    include "Model/Notification.php";

    if (isset($_POST['q1']) && isset($_POST['q2']) && isset($_POST['q3'])) {
        // Define correct answers and explanations
        $questions = [
            'q1' => [
                'correct' => 'B',
                'question' => 'What is Shelter Zimbabwe\'s primary focus?',
                'explanation' => 'Shelter Zimbabwe specializes in affordable housing solutions for Zimbabwean communities.'
            ],
            'q2' => [
                'correct' => 'C',
                'question' => 'Which of these is NOT one of our core values?',
                'explanation' => 'While we value Innovation (A) and Integrity (B), Exclusivity (C) is not part of our core values as we aim to be inclusive in our housing solutions.'
            ],
            'q3' => [
                'correct' => 'A',
                'question' => 'Our current flagship project is:',
                'explanation' => 'The Urban Housing Initiative (A) is our flagship project developing 2,500 affordable units in Harare and Bulawayo.'
            ]
        ];

        // Calculate score
        $score = 0;
        $user_answers = [];
        $total_questions = count($questions);
        
        foreach ($questions as $key => $question) {
            $user_answer = $_POST[$key];
            $user_answers[$key] = [
                'user_answer' => $user_answer,
                'is_correct' => ($user_answer == $question['correct']),
                'explanation' => $question['explanation']
            ];
            
            if ($user_answer == $question['correct']) {
                $score++;
            }
        }

        $percentage = ($score / $total_questions) * 100;

        // Store result in database
        $data = array($_SESSION['employee_id'], $score, $total_questions, $percentage);
        insert_quiz_result($conn, $data);

        // Serialize results for URL
        $results_data = base64_encode(json_encode([
            'score' => $score,
            'total' => $total_questions,
            'percentage' => $percentage,
            'answers' => $user_answers,
            'questions' => $questions
        ]));

        // Return result to user
        header("Location: ../company_info.php?quiz_results=$results_data");
        exit();
    } else {
        $em = "Please answer all questions";
        header("Location: ../company_info.php?error=$em");
        exit();
    }
} else { 
    $em = "First login";
    header("Location: ../login.php?error=$em");
    exit();
}
?>