<?php 
session_start();
if (isset($_SESSION['role']) && isset($_SESSION['employee_id'])) {
    include "DB_connection.php";
    include "app/Model/Notification.php";
    include "app/Model/RoleHelper.php";
    // include "app/Model/User.php";

    // Exclude attendance/team_attendance/activity for MD, HR, and Finance Manager
    // so they see only application-related and other important notifications
    $emp_id = (int)$_SESSION['employee_id'];
    $is_managing_director = RoleHelper::is_managing_director($conn, $emp_id);
    $is_hr = RoleHelper::is_hr($conn, $emp_id);
    $is_finance_manager = (strcasecmp(trim(RoleHelper::get_department($conn, $emp_id) ?? ''), RoleHelper::DEPT_FINANCE) === 0 && RoleHelper::is_manager($conn, $emp_id));
    $exclude_attendance = $is_managing_director || $is_hr || $is_finance_manager;

    // Notifications page shows ALL notifications (read and unread), filtered for MD/HR/FM
    $notifications = get_all_my_notifications($conn, $_SESSION['employee_id'], $exclude_attendance);

    // Mark all as read when user views this page (status changes from unread to read)
    mark_all_notifications_read($conn, $_SESSION['employee_id'], $exclude_attendance);
    if (is_array($notifications)) {
        foreach ($notifications as &$n) { $n['is_read'] = 1; }
        unset($n);
    }

 ?>
<!DOCTYPE html>
<html>
<head>
	<title>Notifications · Shelter HRMS</title>
    <?php include __DIR__ . '/inc/head_common.php'; ?>
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
	<link rel="stylesheet" href="css/style.css">
	<link rel="stylesheet" href="css/aesthetic-improvements.css">

</head>
<body>
	<input type="checkbox" id="checkbox">
	<?php include "inc/header.php" ?>
	<div class="body">
		<?php include "inc/nav.php" ?>
		<section class="section-1">
			<h4 class="title">All Notifications</h4>
			<?php if (isset($_GET['success'])) {?>
      	  	<div class="success" role="alert">
			  <?php echo stripcslashes($_GET['success']); ?>
			</div>
		<?php } ?>
			<?php if ($notifications != 0) { ?>
			<table class="main-table">
				<tr>
					<th>#</th>
					<th>Message</th>
					<th>Type</th>
					<th>Status</th>
					<th>Date</th>
				</tr>
				<?php $i=0; foreach ($notifications as $notification) {
					$msg = $notification['message'];
					if (preg_match('/Loan application from\s+requires/i', $msg)) {
						$msg = preg_replace('/Loan application from\s+requires/i', 'Loan application from (applicant name unknown) requires', $msg);
					}
				?>
				<tr style="<?= $notification['is_read'] == 0 ? 'background-color: #e8f4f8; font-weight: 500;' : '' ?>">
					<td><?=++$i?></td>
					<td><?=htmlspecialchars($msg)?></td>
					<td><?=htmlspecialchars($notification['type'])?></td>
					<td>
						<?php if ($notification['is_read'] == 0): ?>
							<span class="status-badge" style="background: #ffc107;">Unread</span>
						<?php else: ?>
							<span class="status-badge" style="background: #6c757d;">Read</span>
						<?php endif; ?>
					</td>
					<td><?=htmlspecialchars($notification['date'])?></td>
				</tr>
			   <?php	} ?>
			</table>
		<?php }else { ?>
			<h3>You have zero notification</h3>
		<?php  }?>
			
		</section>
	</div>




</body>
</html>
<?php }else{ 
   $em = "First login";
   header("Location: login.php?error=$em");
   exit();
}
 ?>