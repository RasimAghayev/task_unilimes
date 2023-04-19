<?php
// Load the database configuration file
include_once 'db_connection.php';

// Get status message
if(!empty($_GET['status'])){
    switch($_GET['status']){
        case 'succ':
            $statusType = 'alert-success';
            $statusMsg = 'Members data has been imported successfully.';
            break;
        case 'err':
            $statusType = 'alert-danger';
            $statusMsg = 'Some problem occurred, please try again.';
            break;
        case 'invalid_file':
            $statusType = 'alert-danger';
            $statusMsg = 'Please upload a valid CSV file.';
            break;
        default:
            $statusType = '';
            $statusMsg = '';
    }
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bootstrap demo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
  </head>
  <body>
<!-- Display status message -->
<?php if(!empty($statusMsg)){ ?>
<div class="col-xs-12" id='alert'>
    <div class="alert <?php echo $statusType; ?>"><?php echo $statusMsg; ?></div>
</div>
<?php } ?>

<div class="row">
    <!-- Import link -->
    <div class="col-md-9 head">
        <div class="float-left">
            <a href="javascript:void(0);" class="btn btn-success" onclick="formToggle('importFrm');"><i class="plus"></i> Import</a>
        </div>
        <!-- CSV file upload form -->
        <div class="col-md-6" id="importFrm" style="display: none;">
            <form action="importData.php" method="post" enctype="multipart/form-data">
                <input type="file" name="file" />
                <input type="submit" class="btn btn-primary" name="importSubmit" value="IMPORT">
            </form>
        </div>
    </div>
    <div class="col-md-3 head">
    <div id="pagination-table"></div>
</div>
    <!-- Data list table --> 
    <table class="table table-striped table-bordered text-center">
        <thead class="thead-dark">
            <tr>
                <th><input type="text" id='id' name="id" placeholder="find id"></th>
                <th></th>
                <th><input type="text" id='firstname' name="firstname" placeholder="find firstname"></th>
                <th><input type="text" id='lastname' name="lastname" placeholder="find lastname"></th>
                <th>
                    <select name="gender" id="gender">
                        <option value=""></option>
                        <option value="female">female</option>
                        <option value="male">male</option>
                    </select>
                </th>
                <th><input type="text" id='email' name="email" placeholder="find email"></th>
                <th><input type="text" id='birthDate' name="birthDate" placeholder="find birthDate"></th>
                <th><input type="text" id='created_at' name="created_at" placeholder="find created_at"></th>
            </tr>
            <tr>
                <th>#ID</th>
                <th>category</th>
                <th>firstname</th>
                <th>lastname</th>
                <th>gender</th>
                <th>email</th>
                <th>birthDate</th>
                <th>created_at</th>
            </tr>
        </thead>
        <tbody>
        <?php
        // Get member rows
        $result = $db->query("SELECT * FROM customer ORDER BY id DESC limit 10");
        if($result->num_rows > 0){
            while($row = $result->fetch_assoc()){
        ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo $row['category']; ?></td>
                <td><?php echo $row['firstname']; ?></td>
                <td><?php echo $row['lastname']; ?></td>
                <td><?php echo $row['gender']; ?></td>
                <td><?php echo $row['email']; ?></td>
                <td><?php echo $row['birthDate']; ?></td>
                <td><?php echo $row['created_at']; ?></td>
            </tr>
        <?php } }else{ ?>
            <tr><td colspan="8">No member(s) found...</td></tr>
        <?php } ?>
        </tbody>
        <tfoot class="tfoot-dark">
            <tr>
                <th><input type="text" id='idf' name="id" placeholder="find id"></th>
                <th></th>
                <th><input type="text" id='firstnamef' name="firstname" placeholder="find firstname"></th>
                <th><input type="text" id='lastnamef' name="lastname" placeholder="find lastname"></th>
                <th>
                    <select name="gender" id="gender">
                        <option value="volvo">female</option>
                        <option value="saab">male</option>
                    </select>
                </th>
                <th><input type="text" id='emailf' name="email" placeholder="find email"></th>
                <th><input type="text" id='birthDatef' name="birthDate" placeholder="find birthDate"></th>
                <th><input type="text" id='created_atf' name="created_at" placeholder="find created_at"></th>
            </tr>
        </tfoot>
    </table>
</div>

<!-- Show/hide CSV upload form -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="pagination.js"></script>
  
<script>
function formToggle(ID){
    var element = document.getElementById(ID);
    if(element.style.display === "none"){
        element.style.display = "block";
    }else{
        element.style.display = "none";
    }
}
    window.onload = function() {
        var duration = 2000; //2 seconds
        setTimeout(function () { $('#alert').hide(); }, 2000);
    };


    $(function() {
    (function(name) {
        var container = $('#pagination-' + name);
        if (!container.length) return;
        container.pagination({
        dataSource: 'http://localhost:8000/restData.php?age=30~35',
        locator: 'items',
        pageNumber: 2,
        totalNumber: 120,
        pageSize: 20,
        ajax: {
            beforeSend: function() {
            container.prev().html('Loading data from flickr.com ...');
            }
        },
        callback: function(response, pagination) {
            window.console && console.log(22, response, pagination);
            $.each(response, function (index, item) {
            var dataHtml = '<tr>';
                dataHtml += '<td>' + item.id + '</td>';
                dataHtml += '<td>' + item.category + '</td>';
                dataHtml += '<td>' + item.firstname + '</td>';
                dataHtml += '<td>' + item.lastname + '</td>';
                dataHtml += '<td>' + item.gender + '</td>';
                dataHtml += '<td>' + item.email + '</td>';
                dataHtml += '<td>' + item.birthDate + '</td>';
                dataHtml += '<td>' + item.created_at + '</td>';
                dataHtml += '</tr>';
            });


            container.prev().html(dataHtml);
        }
        })
    })('table');
})

</script></body>
</html>