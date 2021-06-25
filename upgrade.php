<style>
    * {font-family: Arial}
    body {padding: 20px}
</style>
<?php
## Helper Functions
function __pr($o){
    echo "<pre>";
    print_r($o);
    echo "</pre>";
}

## Constant
define('MYCRO_CONFIG', '__mycro.config');

## Saving & Checking
if(isset($_POST['databaseType'])){
    if($_POST['databaseType'] == 'ec2'){
        ## Force override redis & multi-az to N
        $_POST['ec-redis'] = $_POST['rds-multiaz'] = 'N';
    }
    
    $_POST['ec-redis'] = empty($_POST['ec-redis']) ? 'N' : $_POST['ec-redis'];
    $_POST['rds-multiaz'] = empty($_POST['rds-multiaz']) ? 'N' : $_POST['rds-multiaz'];
     
    
    ## Check if differences compare to config: 
    $configRaw = @file_get_contents(MYCRO_CONFIG);
    $config = json_decode($configRaw, /*$associative*/ true);
    
    $diff = [];
    foreach($_POST as $k => $v){
        if($_POST[$k] != $config[$k]) 
            $diff[$k] = ['o'=> $config[$k], 'n'=> $_POST[$k]];
    }
    
    if(!empty($diff)){
        __pr($diff);
        ## sample: 
        exec('./dbMigrate.sh');
        header("Location: https://console.aws.amazon.com/cloudformation/home?#/stacks/new?stackName=add_RDS&templateURL=https://quick-launch-ecomm.s3.us-east-2.amazonaws.com/RDStemplate.yaml");
        
    }
    
    file_put_contents(MYCRO_CONFIG, json_encode($_POST, JSON_PRETTY_PRINT));
}

$configRaw = @file_get_contents(MYCRO_CONFIG);
$config = json_decode($configRaw, /*$associative*/ true);
## __pr($config);

## Parameters Configuration
$databaseOptions = [
    'ec2' => 'Default - Database on the same machine',
    'rds' => 'Amazon RDS - MySQL',
    'aurora' => 'Amazon RDS Aurora - MySQL'
];


#### Format Form Options
## dropdown
$dtype = isset($config['databaseType']) ? $config['databaseType'] : '';
$form = [];
$tmp = '';
foreach($databaseOptions as $key => $val){
    $selected = ($key == $dtype) ? 'selected' : '';
    $tmp .= "<option value='$key' $selected>$val</option>";
}

$form[] = $tmp;

## checkbox
$useCache = isset($config['ec-redis']) && $config['ec-redis'] == 'Y' ? 'checked' : '';
$useMultiAZ = isset($config['rds-multiaz']) && $config['rds-multiaz'] == 'Y' ? 'checked' : '';

#### Printing Form

echo "
<h1>E-Commerce Setup - v0.1</h1>
<form method=POST>
    Database Type: <select id=databaseType name=databaseType len=40>
        ".implode($form, '')."
    </select><br>
    <label for=ec-redis>Enable Redis on Amazon ElastiCache</label>
    <input type=checkbox id=ec-redis name=ec-redis value='Y' $useCache><br>
    
    <label for=rds-multiaz>Enable RDS Multi-AZ</label>
    <input type=checkbox id=rds-multiaz name=rds-multiaz value='Y' $useMultiAZ><br>
    
    <input type=submit value='Save'>
</form>
";

?>

<script
  src="https://code.jquery.com/jquery-3.6.0.min.js"
  integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4="
  crossorigin="anonymous"></script>
<script>
    function checkIfSelectedDatabaseTypeIsRDS(el){
        return el.val() != 'ec2' ? true : false;
    }
    
    function triggerCheckboxes(src, tgt){
        if (checkIfSelectedDatabaseTypeIsRDS(src)){
            tgt.prop( "disabled", false );
        }else{
            tgt.prop( "disabled", true );
        }
    }
    
    $(document).ready(function(){
        var databaseTypeEl = $('#databaseType');
        var checkBoxes = $('#ec-redis, #rds-multiaz');
        
        triggerCheckboxes(databaseTypeEl, checkBoxes)
        databaseTypeEl.change(function(){
            var t = $(this)
            triggerCheckboxes(t, checkBoxes)
        })
        
    })
</script>
