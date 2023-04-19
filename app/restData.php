<?php
// Load the database configuration file
include_once 'db_connection.php';
header('Content-type: application/json; charset=utf-8');
$column = $db->query('DESCRIBE customer');
$newColumn=[];
$whereQuery=[];
$keyGets=['limit'=>100,'age'=>'0','page'=>1];
while($row=$column->fetch_assoc()){
    $newColumn[$row['Field']]=$row['Type'];
}
foreach($_GET as $k=>$v){
    if(array_key_exists($k,$newColumn)){
        $whereQuery[]=$k." like '%".$v."%' ";
    }elseif(array_key_exists($k,$keyGets)){
        if($k=='age'){
            $explodeAge=explode('~',$v);
            
            $whereQuery[]=$k. ((count($explodeAge)>=2 
                                    && $explodeAge[0]<$explodeAge[1])?
                                                " between ".$explodeAge[0]." and ".$explodeAge[1]:
                                                '<'.$explodeAge[0]);
        }else{
            $limit=($k=='limit')?$v:$keyGets[$k];
        }
    }
}
$whereQuerys=(count($whereQuery)>0)?'where '.implode(' and ',$whereQuery):'';
$limit='limit '.(empty($limit)?100:$limit);
// Get member rows
$sql="Select * from (SELECT 
*,TIMESTAMPDIFF(YEAR, str_to_date(birthDate, '%Y-%m-%d'), current_date) AS age 
FROM customer) customer_list
{$whereQuerys}
ORDER BY id DESC {$limit}";
$result = $db->query($sql)-> fetch_all(MYSQLI_ASSOC);
echo json_encode([$whereQuerys,$limit,$result]);