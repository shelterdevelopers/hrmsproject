<?php 
session_start();
if (isset($_SESSION['role']) && isset($_SESSION['employee_id'])) {
    include "DB_connection.php";
    include "app/Model/Notification.php";
    // include "app/Model/User.php";

 ?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>About · Shelter HRMS</title>
	<?php include "inc/head_common.php"; ?>
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
	<link rel="stylesheet" href="css/style.css">
</head>
<body>
	<input type="checkbox" id="checkbox">
	<?php include "inc/header.php" ?>
	<div class="body">
		<?php include "inc/nav.php" ?>
		<section class="section-1">
    <div class="content-box">
        <!-- Company Information Section -->
        <div class="about-section">
            <h3><i class="fa fa-building"></i> About Shelter Zimbabwe</h3>
            <p>Shelter Zimbabwe is a leading housing finance and development institution committed to providing affordable and sustainable housing solutions across Zimbabwe. We work with various stakeholders to transform the housing landscape in the country.</p>
            
            <div class="mission-vision">
                <div class="mv-item">
                    <h4><i class="fa fa-eye"></i> Vision</h4>
                    <p>To be the premier housing finance and development institution in Zimbabwe.</p>
                </div>
                <div class="mv-item">
                    <h4><i class="fa fa-bullseye"></i> Mission</h4>
                    <p>To provide innovative housing solutions that transform people's lives through sustainable and affordable housing.</p>
                </div>
            </div>
        </div>
        
        <!-- Ongoing Projects Section -->
        <div class="projects-section">
            <h3><i class="fa fa-home"></i> Current Projects</h3>
            <div class="project-cards">
                <div class="project-card">
                    <h4>Urban Housing Initiative</h4>
                    <p>Developing 2,500 affordable housing units in Harare and Bulawayo.</p>
                    <div class="progress-container">
                        <div class="progress-bar" style="width: 75%">75%</div>
                    </div>
                    <p class="project-status"><i class="fa fa-calendar"></i> Expected completion: Q1 2024</p>
                </div>
                
                <div class="project-card">
                    <h4>Rural Housing Program</h4>
                    <p>Building 1,200 low-cost homes in rural communities.</p>
                    <div class="progress-container">
                        <div class="progress-bar" style="width: 40%">40%</div>
                    </div>
                    <p class="project-status"><i class="fa fa-calendar"></i> Expected completion: Q3 2024</p>
                </div>
            </div>
        </div>
        
        <!-- Onboarding Assessment Section -->
        <div class="review-section">
            <h3><i class="fa fa-star"></i> Onboarding Assessment</h3>
            
            <div class="assessment-tabs">
                <button class="tab-btn active" onclick="openTab(event, 'quiz')">Knowledge Quiz</button>
                <button class="tab-btn" onclick="openTab(event, 'feedback')">Feedback</button>
            </div>
            
            <!-- Quiz Tab -->
<div id="quiz" class="tab-content" style="display: block;">
    <?php 
    // Check for quiz results in URL
    if (isset($_GET['quiz_results'])) {
        $results = json_decode(base64_decode($_GET['quiz_results']), true);
    ?>
        <div class="quiz-results">
            <h4>Your Quiz Results</h4>
            <div class="score-summary">
                <p>You scored <strong><?=$results['score']?> out of <?=$results['total']?></strong> (<?=round($results['percentage'], 2)?>%)</p>
            </div>
            
            <div class="question-review">
                <h5>Question Review:</h5>
                <?php foreach ($results['questions'] as $key => $question): ?>
                <div class="question-item <?=$results['answers'][$key]['is_correct'] ? 'correct' : 'incorrect'?>">
                    <p><strong>Question:</strong> <?=$question['question']?></p>
                    <p><strong>Your answer:</strong> <?=$results['answers'][$key]['user_answer']?></p>
                    <p><strong>Correct answer:</strong> <?=$question['correct']?></p>
                    <p class="explanation"><strong>Explanation:</strong> <?=$question['explanation']?></p>
                </div>
                <?php endforeach; ?>
            </div>
            
            <a href="company_info.php" class="btn">Return to Onboarding</a>
        </div>
    <?php } 
                // Check if quiz has already been submitted in database
                elseif (($quiz_result = get_quiz_result($conn, $_SESSION['employee_id'])) != 0) { 
                ?>
                    <div class="quiz-results">
                        <h4>Your Previous Quiz Results</h4>
                        <div class="score-summary">
                            <p>You scored <strong><?=$quiz_result['score']?> out of <?=$quiz_result['total_questions']?></strong> (<?=round($quiz_result['percentage'], 2)?>%)</p>
                            <p>Submitted on: <?=date('d M Y H:i', strtotime($quiz_result['submitted_at']))?></p>
                        </div>
                        <p>You've already completed the quiz. Contact HR if you need to retake it.</p>
                    </div>
                <?php } else { ?>
                    <form id="onboardingQuiz" action="app/process_quiz.php" method="post">
                        <div class="quiz-question">
                            <p>1. What is Shelter Zimbabwe's primary focus?</p>
                            <label><input type="radio" name="q1" value="A" required> Commercial real estate development</label><br>
                            <label><input type="radio" name="q1" value="B"> Affordable housing solutions</label><br>
                            <label><input type="radio" name="q1" value="C"> Luxury properties</label>
                        </div>
                        
                        <div class="quiz-question">
                            <p>2. Which of these is NOT one of our core values?</p>
                            <label><input type="radio" name="q2" value="A" required> Innovation</label><br>
                            <label><input type="radio" name="q2" value="B"> Integrity</label><br>
                            <label><input type="radio" name="q2" value="C"> Exclusivity</label>
                        </div>
                        
                        <div class="quiz-question">
                            <p>3. Our current flagship project is:</p>
                            <label><input type="radio" name="q3" value="A" required> Urban Housing Initiative</label><br>
                            <label><input type="radio" name="q3" value="B"> Coastal Resort Development</label><br>
                            <label><input type="radio" name="q3" value="C"> Industrial Parks Expansion</label>
                        </div>
                        
                        <button type="submit" class="btn submit-btn">Submit Quiz</button>
                    </form>
                <?php } ?>
            </div>
            
            <!-- Feedback Tab -->
            <div id="feedback" class="tab-content">
                <form id="feedbackForm" action="app/process_feedback.php" method="post">
                    <div class="input-holder">
                        <label>Your Feedback:</label>
                        <textarea class="input-1" name="feedback" rows="5" placeholder="Share your onboarding experience..." required></textarea>
                    </div>
                    
                    <div class="rating-section">
                        <p>Rate your onboarding experience:</p>
                        <div class="stars">
                            <i class="fa fa-star" onmouseover="rateHover(1)" onclick="rate(1)"></i>
                            <i class="fa fa-star" onmouseover="rateHover(2)" onclick="rate(2)"></i>
                            <i class="fa fa-star" onmouseover="rateHover(3)" onclick="rate(3)"></i>
                            <i class="fa fa-star" onmouseover="rateHover(4)" onclick="rate(4)"></i>
                            <i class="fa fa-star" onmouseover="rateHover(5)" onclick="rate(5)"></i>
                        </div>
                        <input type="hidden" id="rating" name="rating" value="0">
                    </div>
                    
                    <button type="submit" class="btn submit-btn">Submit Feedback</button>
                </form>
            </div>
        </div>
    </div>
</section>

<script type="text/javascript">
	
	
	// Tab functionality
	function openTab(evt, tabName) {
		var i, tabcontent, tabbuttons;
		
		tabcontent = document.getElementsByClassName("tab-content");
		for (i = 0; i < tabcontent.length; i++) {
			tabcontent[i].style.display = "none";
		}
		
		tabbuttons = document.getElementsByClassName("tab-btn");
		for (i = 0; i < tabbuttons.length; i++) {
			tabbuttons[i].className = tabbuttons[i].className.replace(" active", "");
		}
		
		document.getElementById(tabName).style.display = "block";
		evt.currentTarget.className += " active";
	}
	
	// Star rating functionality
	let currentHover = 0;
	let currentRating = 0;
	
	function rateHover(star) {
		currentHover = star;
		updateStars();
	}
	
	function rate(star) {
		currentRating = star;
		document.getElementById('rating').value = star;
		updateStars();
	}
	
	function updateStars() {
		const stars = document.querySelectorAll('.stars i');
		const value = currentHover || currentRating;
		
		stars.forEach((star, index) => {
			if (index < value) {
				star.classList.add('rated');
			} else {
				star.classList.remove('rated');
			}
		});
	}
	
	// Initialize stars on page load
	document.addEventListener('DOMContentLoaded', function() {
		const stars = document.querySelectorAll('.stars i');
		stars.forEach(star => {
			star.addEventListener('mouseout', function() {
				if (!currentRating) {
					updateStars();
				}
			});
		});
	});
</script>


</body>
</html>
<?php }else{ 
   $em = "First login";
   header("Location: login.php?error=$em");
   exit();
}
 ?>