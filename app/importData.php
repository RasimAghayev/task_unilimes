<?php
// Load the database configuration file
include_once 'db_connection.php';
set_time_limit(1000);
if(isset($_POST['importSubmit'])){
    
    // Allowed mime types
    $csvMimes = array('text/x-comma-separated-values', 'text/comma-separated-values', 
    'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 
    'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel', 
    'text/plain');
    
    // Validate whether selected file is a CSV file
    if(!empty($_FILES['file']['name']) && in_array($_FILES['file']['type'], $csvMimes)){
        
        // If the file is uploaded
        if(is_uploaded_file($_FILES['file']['tmp_name'])){
            
            // Open uploaded CSV file with read-only mode
            $csvFile = fopen($_FILES['file']['tmp_name'], 'r');
            
            // Skip the first line
            fgetcsv($csvFile);
            
            // Parse data from CSV file line by line
            while(($line = fgetcsv($csvFile)) !== FALSE){
                // Get row data
                $category   = $line[0];
                $firstname  = $line[1];
                $lastname  = $line[2];
                $email = $line[3];
                $gender = $line[4];
                $birthDate = $line[5];
                
                // Check whether member already exists in the database with the same email
                $prevQuery = "SELECT id FROM customer WHERE email = '".$email."'";
                $prevResult = $db->query($prevQuery);
                
                if($prevResult->num_rows > 0){
                    // Update member data in the database
                    $db->query("UPDATE customer 
                                   SET category = '".$category."',
                                       firstname = '".htmlspecialchars($firstname)."', 
                                       lastname = '".htmlspecialchars($lastname)."', 
                                       email = '".$email."', 
                                       gender = '".$gender."', 
                                       birthDate = '".$birthDate."', 
                                       updated_at = NOW() 
                                 WHERE email = '".$email."'");
                }else{
                    // Insert member data in the database
                    $db->query("INSERT INTO customer (
                                                        category, 
                                                        firstname, 
                                                        lastname, 
                                                        email, 
                                                        gender, 
                                                        birthDate, 
                                                        created_at, 
                                                        updated_at) 
                                     VALUES ('".
                                                $category
                                                ."', '".
                                                htmlspecialchars($firstname)
                                                ."', '".
                                                htmlspecialchars($lastname)
                                                ."', '".
                                                $email
                                                ."', '".
                                                $gender
                                                ."', '".
                                                $birthDate
                                                ."', 
                                                NOW(), 
                                                NOW()
                                                )");
                }
            }
            
            // Close opened CSV file
            fclose($csvFile);
            
            $qstring = '?status=succ';
        }else{
            $qstring = '?status=err';
        }
    }else{
        $qstring = '?status=invalid_file';
    }
}

// Redirect to the listing page
header("Location: index.php".$qstring);