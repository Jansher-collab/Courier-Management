<?php
session_start();
include '../includes/db.php';
include '../includes/functions.php';
include '../includes/mail.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin'){
    header("Location: login.php");
    exit();
}

$message_sent = '';

// Fetch all couriers (booked, in-progress, or delivered)
$query = "SELECT 
            c.courier_id, 
            r.name AS receiver_name, 
            r.email AS receiver_email,
            c.to_location,
            c.tracking_number,
            c.status
          FROM couriers c
          JOIN customers r ON c.receiver_id = r.customer_id
          WHERE c.status IN ('booked', 'in-progress', 'delivered')
          ORDER BY c.courier_id ASC";

$result = $conn->query($query);
$couriers = [];
while($row = $result->fetch_assoc()) $couriers[] = $row;

$selected_courier_id = $_POST['courier_id'] ?? '';

if(isset($_POST['send_delivery_email']) && !empty($selected_courier_id)){

    $courier_id = $selected_courier_id;

    $stmt_c = $conn->prepare(
        "SELECT r.name, r.email, c.to_location, c.tracking_number, c.status 
         FROM couriers c 
         JOIN customers r ON c.receiver_id = r.customer_id 
         WHERE c.courier_id = ?"
    );

    $stmt_c->bind_param("i", $courier_id);
    $stmt_c->execute();
    $courier = $stmt_c->get_result()->fetch_assoc();

    if($courier){

        $subject = "Courier Status Update";

        $body =
"Hello ".$courier['name'].",

Your courier with tracking number ".$courier['tracking_number']." 
is currently ".$courier['status']." and scheduled for delivery to ".$courier['to_location'].".

Thank you for using our Courier Management System.";

        if(send_mail($courier['email'], $subject, $body)){

            $message_sent = "Delivery email sent to "
                . htmlspecialchars($courier['name'])
                . " (" . htmlspecialchars($courier['email']) . ")";

            $stmt_log = $conn->prepare(
                "INSERT INTO courier_logs (courier_id, status, message, notified_via)
                 VALUES (?, ?, ?, 'email')"
            );

            $status_msg = "Delivery email sent by admin";
            $stmt_log->bind_param("iss", $courier_id, $status_msg, $body);
            $stmt_log->execute();

        } else {
            $message_sent = "Failed to send email.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Send Delivery Email</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:'Segoe UI',sans-serif;}
html{scrollbar-width:none;} html::-webkit-scrollbar{display:none;}
body{
    background:url('../assets/admin-delivery-sms.jpg') center/cover no-repeat fixed;
    position:relative;
    padding-bottom:120px;
}
body::after{
    content:'';
    position:fixed;
    top:0;left:0;width:100%;height:100%;
    background:rgba(0,0,0,0.35);
    z-index:-1;
}

/* Navbar */
.navbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding:15px 30px;
    position:fixed;
    top:0;
    width:100%;
    z-index:999;
}
.logo{ color:#ff7e5f;font-size:1.5rem;font-weight:bold;}
.nav-buttons{display:flex;gap:10px;}
.btn{
    text-decoration:none;
    padding:12px 20px;
    border-radius:10px;
    font-weight:bold;
    color:white;
    transition:0.3s;
}
.dashboard{
    background:linear-gradient(135deg,#ffd200,#f7971e);
}
.logout{
    background:linear-gradient(135deg,#ff7e5f,#feb47b);
}

/* Container */
.container{
    width:95%;
    max-width:700px;
    margin:100px auto 50px auto;
    background:rgba(255,255,255,0.15);
    backdrop-filter:blur(15px);
    border-radius:20px;
    padding:30px;
    color:#fff;
    box-shadow:0 10px 30px rgba(0,0,0,0.25);
    position:relative;
}
h2{text-align:center;margin-bottom:20px;}
p.message{text-align:center;font-weight:bold;margin-bottom:10px;color:#00ff88;}

/* Status Filter Buttons */
.status-filters{
    display:flex;
    gap:10px;
    justify-content:center;
    margin-bottom:25px;
    flex-wrap:wrap;
}
.filter-btn{
    padding:10px 20px;
    border-radius:10px;
    border:2px solid rgba(255,255,255,0.3);
    background:rgba(255,255,255,0.1);
    color:#fff;
    cursor:pointer;
    font-weight:bold;
    transition:0.3s;
    font-size:0.9rem;
}
.filter-btn:hover{
    background:rgba(255,255,255,0.2);
    transform:translateY(-2px);
}
.filter-btn.active{
    background:linear-gradient(135deg,#ff7e5f,#feb47b);
    border-color:#ff7e5f;
    box-shadow:0 4px 15px rgba(255,126,95,0.4);
}
.count-badge{
    display:inline-block;
    background:rgba(255,255,255,0.3);
    padding:2px 8px;
    border-radius:12px;
    font-size:0.8rem;
    margin-left:5px;
}
.filter-btn.active .count-badge{
    background:rgba(255,255,255,0.4);
}

/* Dropdown */
.dropdown-wrapper{position:relative;width:100%;margin-bottom:20px;z-index:100;}
.dropdown-selected{
    width:100%;
    padding:12px;
    border-radius:10px;
    background:#fff;
    color:#000;
    cursor:pointer;
    position:relative;
    overflow:hidden;
    text-overflow:ellipsis;
    white-space:nowrap;
    border:none;
    outline:none;
    transition:0.3s;
}
.dropdown-selected.active{
    box-shadow:0 0 15px 4px rgba(255,126,95,0.9);
    border:1px solid #ff7e5f;
}
.dropdown-selected::after{
    content:"▼";
    position:absolute;
    right:15px;
    top:50%;
    transform:translateY(-50%);
}
.dropdown-items{
    position:absolute;
    width:100%;
    max-width:100%;
    max-height:250px;
    overflow-y:auto;
    overflow-x:hidden;
    background:#fff;
    border-radius:10px;
    top:calc(100% + 5px);
    left:0;
    box-shadow:0 8px 25px rgba(0,0,0,0.3);
    display:none;
    z-index:1000;
}
.dropdown-items div{
    padding:10px;
    cursor:pointer;
    color:#000;
    word-break:break-word;
    transition:0.3s;
}
.dropdown-items div:hover{
    background:linear-gradient(135deg,#ff7e5f,#feb47b);
    color:#fff;
}
.dropdown-items div.no-results{
    text-align:center;
    color:#999;
    cursor:default;
    font-style:italic;
}
.dropdown-items div.no-results:hover{
    background:transparent;
    color:#999;
}
.search-box{
    padding:10px;
    border:none;
    border-bottom:1px solid #ccc;
    outline:none;
    width:100%;
    position:sticky;
    top:0;
    background:#fff;
    z-index:10;
}

/* Button */
button{
    display:block;
    margin:25px auto 0 auto;
    padding:12px 30px;
    border-radius:10px;
    border:none;
    cursor:pointer;
    font-size:1rem;
    font-weight:bold;
    background:linear-gradient(135deg,#ff7e5f,#feb47b);
    color:#fff;
    transition:0.3s;
}
button:hover{
    transform:translateY(-2px);
    box-shadow:0 6px 20px rgba(0,0,0,0.25);
}
input:focus, textarea:focus{
    box-shadow:0 0 15px 4px rgba(255,126,95,0.9);
    border:1px solid #ff7e5f;
}
</style>
</head>

<body>

<div class="navbar">
    <div class="logo">Courier Admin</div>
    <div class="nav-buttons">
        <a href="dashboard.php" class="btn dashboard">Dashboard</a>
        <a href="../logout.php" class="btn logout">Logout</a>
    </div>
</div>

<div class="container">

<h2>Send Delivery Notification Email (Admin)</h2>

<?php if($message_sent) echo "<p class='message'>$message_sent</p>"; ?>

<!-- Status Filter Buttons -->
<div class="status-filters">
    <button type="button" class="filter-btn active" data-status="all">All <span class="count-badge" id="count-all">0</span></button>
    <button type="button" class="filter-btn" data-status="booked">Booked <span class="count-badge" id="count-booked">0</span></button>
    <button type="button" class="filter-btn" data-status="in-progress">In-Progress <span class="count-badge" id="count-in-progress">0</span></button>
    <button type="button" class="filter-btn" data-status="delivered">Delivered <span class="count-badge" id="count-delivered">0</span></button>
</div>

<form method="POST">

<div class="dropdown-wrapper">

<label>Select Courier:</label>

<div class="dropdown-selected" id="selected">
<?= $selected_courier_id ? "Courier ID: $selected_courier_id" : "--Select Courier--" ?>
</div>

<div class="dropdown-items" id="dropdown-items">
<input type="text" id="searchInput" class="search-box" placeholder="Search by ID, Name, Email, Location or Status...">
<?php foreach($couriers as $c): ?>
<div data-id="<?= $c['courier_id'] ?>"
     data-status="<?= $c['status'] ?>"
     data-search="<?= strtolower($c['courier_id'].' '.$c['receiver_name'].' '.$c['receiver_email'].' '.$c['to_location'].' '.$c['status']) ?>">
<?= "Courier ID: {$c['courier_id']} - " . htmlspecialchars($c['receiver_name']) . " ({$c['receiver_email']}) to " . htmlspecialchars($c['to_location']) . " [{$c['status']}]" ?>
</div>
<?php endforeach; ?>
</div>

<input type="hidden" name="courier_id" id="courier_id_val" value="<?= $selected_courier_id ?>">

</div>

<button type="submit" name="send_delivery_email">Send Delivery Email</button>

</form>

</div>

<script>
// Dropdown selection
const selected=document.getElementById('selected');
const items=document.getElementById('dropdown-items');
const hiddenInput=document.getElementById('courier_id_val');
const searchInput=document.getElementById('searchInput');
const filterBtns=document.querySelectorAll('.filter-btn');
const courierItems=document.querySelectorAll('.dropdown-items div[data-id]');

let currentFilter='all';

// Calculate and display counts on page load
function updateCounts(){
    let allCount=0;
    let bookedCount=0;
    let inProgressCount=0;
    let deliveredCount=0;
    
    courierItems.forEach(div=>{
        const status=div.getAttribute('data-status');
        allCount++;
        if(status==='booked') bookedCount++;
        if(status==='in-progress') inProgressCount++;
        if(status==='delivered') deliveredCount++;
    });
    
    document.getElementById('count-all').textContent=allCount;
    document.getElementById('count-booked').textContent=bookedCount;
    document.getElementById('count-in-progress').textContent=inProgressCount;
    document.getElementById('count-delivered').textContent=deliveredCount;
}

// Call on page load
updateCounts();

selected.addEventListener('click',()=>{
    items.style.display=items.style.display==='block'?'none':'block';
    selected.classList.toggle('active');
    if(items.style.display==='block'){
        searchInput.focus();
    }
});

courierItems.forEach(div=>{
    div.addEventListener('click',()=>{
        selected.textContent=div.textContent;
        hiddenInput.value=div.getAttribute('data-id');
        items.style.display='none';
        selected.classList.remove('active');
    });
});

// Search functionality
searchInput.addEventListener('keyup',()=>{
    applyFilters();
});

// Status filter functionality
filterBtns.forEach(btn=>{
    btn.addEventListener('click',()=>{
        // Update active button
        filterBtns.forEach(b=>b.classList.remove('active'));
        btn.classList.add('active');
        
        // Update current filter
        currentFilter=btn.getAttribute('data-status');
        
        // Reset search when changing filter
        searchInput.value='';
        
        // Apply filters
        applyFilters();
    });
});

// Combined filter function
function applyFilters(){
    const searchValue=searchInput.value.toLowerCase();
    let visibleCount=0;
    
    courierItems.forEach(div=>{
        const status=div.getAttribute('data-status');
        const searchText=div.dataset.search;
        
        // Check status filter
        const statusMatch=currentFilter==='all' || status===currentFilter;
        
        // Check search filter
        const searchMatch=searchText.includes(searchValue);
        
        // Show only if both conditions match
        if(statusMatch && searchMatch){
            div.style.display='block';
            visibleCount++;
        }else{
            div.style.display='none';
        }
    });
    
    // Show/hide no results message
    let noResultsDiv=document.getElementById('no-results-msg');
    
    if(visibleCount===0){
        if(!noResultsDiv){
            noResultsDiv=document.createElement('div');
            noResultsDiv.id='no-results-msg';
            noResultsDiv.className='no-results';
            noResultsDiv.textContent='No couriers found';
            items.appendChild(noResultsDiv);
        }
        noResultsDiv.style.display='block';
    }else{
        if(noResultsDiv){
            noResultsDiv.style.display='none';
        }
    }
}

// Close dropdown when clicking outside
document.addEventListener('click',(e)=>{
    if(!selected.contains(e.target)&&!items.contains(e.target)){
        items.style.display='none';
        selected.classList.remove('active');
    }
});

// Prevent dropdown from closing when clicking search box
searchInput.addEventListener('click',(e)=>{
    e.stopPropagation();
});
</script>

</body>
</html>