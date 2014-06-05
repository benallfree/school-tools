<?php
  
define('MATH_MIN',1);
define('MATH_MAX', 30);
define('MATH_MAX_DEFAULT', 12);
define('MATH_MIN_DEFAULT', 1);
define('MATH_HIDE_DEFAULT', .5);
define('MATH_HIDE_MIN', 0.1);
define('MATH_HIDE_MAX', .9);
define('MATH_DEFAULT_OP', '+');

function q($name, $default=null)
{
  if(!isset($_GET[$name])) return $default;
  return $_GET[$name];
}

function limit($n,$min,$max)
{
  if(!is_numeric($n)) return $min;
  $n = (int)$n;
  if($n<$min) return $min;
  if($n>$max) return $max;
  return $n;
}

function bookends($n1, $n2, $min, $max)
{
  $n1 = limit($n1,$min,$max);
  $n2 = limit($n2,$min,$max);
  $n1 = limit($n1,$min,$n2);
  $n2 = limit($n2,$n1,$max);
  return array($n1,$n2);
}

list($min,$max) = bookends(q('min', MATH_MIN_DEFAULT), q('max', MATH_MAX_DEFAULT), MATH_MIN, MATH_MAX);
$skip = limit(q('skip', MATH_HIDE_DEFAULT)*100,0,85)/100.0;
$skip_count = round((pow($max-$min+2,2))*$skip);

$op = q('o',MATH_DEFAULT_OP);
$ops = array(
  '+'=>'Addition',
  '-'=>'Subtraction',
  '/'=>'Division',
  '*'=>'Multiplication',
);

$row = array();
for($i=$min;$i<=$max;$i++) $row[] = $i;
$col = $row;

shuffle($row);
shuffle($col);

$key = uniqid();

$coords = array();
$table = array();
for($r = -1; $r < count($row); $r++)
{
  $tmp = array();
  for($c = -1; $c < count($col); $c++)
  {
    if($r==-1 && $c==-1)
    {
      $tmp[] = $op;
      continue;
    }
    $coords[] = array($r+1,$c+1);
    if($r==-1)
    {
      $tmp[] = $col[$c];
      continue;
    }
    if($c==-1)
    {
      $tmp[] = $row[$r];
      continue;
    }
    switch($op)
    {
      case '+':
        $tmp[] = $row[$r] + $col[$c];
        break;
      case '-':
        $tmp[] = $row[$r] - $col[$c];
        break;
      case '/':
        $tmp[] = $row[$r] / $col[$c];
        break;
      case '*':
      default:
        $tmp[] = $row[$r] * $col[$c];
      
    }
  }
  $table[] = $tmp;
}

/* Shuffle, making sure that there is at least one number in each row and column */
shuffle($coords);
$track = array();
$max_rc = $max-$min;
for($i=0;$i<count($coords);$i++)
{
  list($row,$col) = $coords[$i];
  $rk = "r{$row}";
  if(!isset($track[$rk])) $track[$rk] = 0;
  if($track[$rk]>=$max_rc) continue;
  $ck = "c{$col}";
  if(!isset($track[$ck])) $track[$ck] = 0;
  if($track[$ck]>=$max_rc) continue;
  $track[$rk]++;
  $track[$ck]++;
  $table[$row][$col] = "<b>{$table[$row][$col]}</b>";
  $skip_count--;
  if($skip_count==0) break;
}
?>
<html>
<head>
  <link rel="stylesheet" href="//code.jquery.com/ui/1.10.4/themes/smoothness/jquery-ui.css">
  <script src="//code.jquery.com/jquery-1.10.2.js"></script>
  <script src="//code.jquery.com/ui/1.10.4/jquery-ui.js"></script>
  <script>
  $(function() {
    $( "#slider-range" ).slider({
      range: true,
      min: <?=MATH_MIN?>,
      max: <?=MATH_MAX?>,
      values: [ <?=$min?>, <?=$max?> ],
      slide: function( event, ui ) {
        $('#min').val( ui.values[ 0 ]);
        $('#max').val( ui.values[ 1 ]);
        $( "#range" ).text( ui.values[ 0 ] + " - " + ui.values[ 1 ] );
      }
    });
    $( "#range" ).text( $( "#slider-range" ).slider( "values", 0 ) +
      " - " + $( "#slider-range" ).slider( "values", 1 ) );
    $( "#slider-skip" ).slider({
      min: <?=MATH_HIDE_MIN?>,
      max: <?=MATH_HIDE_MAX?>,
      value: <?=$skip?>,
      step: .05,
      slide: function( event, ui ) {
        $('#skip').val( ui.value);
        $( "#skip_label" ).text( parseInt(parseFloat(ui.value)*100)+ "%");
      }
    });
    $( "#skip_label" ).text( parseInt((parseFloat($( "#slider-skip" ).slider( "value" ))*100)) + "%");
  });
  </script>
  <style>
  body
  {
    font-family: verdana;
    font-size: 15px;
  }
  .page td
  {
    text-align: center;
    padding: 7px;
    border: 1px solid black;
    font-size: 16px;
    width: 44px;
    height: 57px;
  }
  .page td b { display: none;}
  .page.key td b { display: block;}
  @media print
  {
    form { display: none;}
    .page table
    {
    }
    .page td
    {
      text-align: center;
      padding: 7px;
      border: 1px solid black;
      font-size: 12px;
      width: 25px;
      height: 40px;
    }
  }  
  </style>
</head>
<body>
  <form>
    <h1>The Math Table Puzzle</h1>
    <p><b>About:</b> This math solver will generate a table with blanks to fill in. Some of the squares are hidden and must be deduced by solving the other numbers around it first.
    <p><b>Strategy tips:</b>
      <ul>
        <li>Solve the outer numbers first. They are not in order.
        <li>If you have one inner number and one outer number, you can solve for the other outer number using division.
        <li>Two inner numbers can sometimes indicate what one or both of the outer numbers should be. For example, 25 can only be 5x5.
        <li>The outer numbers are the same across the top and down the side, but not necessarily in the same order.
      </ul>
    </p>
    <p><b>Instructions:</b>Fill out the form below to generate an answer key and solver sheet. Choose minimum and maximum numbers as well as a % of numbers to leave empty for your child to solve.
    </p>
    <table border=0>
      <tr>
        <th>Operator</th>
        <td style="width: 200px">
          <select name="o">
            <option value="+" <?= $op=='+' ? 'selected' : ''?>>Addition (+)</option>
            <option value="-" <?= $op=='-' ? 'selected' : ''?>>Subtraction (-)</option>
            <option value="*" <?= $op=='*' ? 'selected' : ''?>>Multiplication (*)</option>
            <option value="/" <?= $op=='/' ? 'selected' : ''?>>Division (/)</option>
          </select>
      </tr>
      <tr>
        <th>Range</th>
        <td style="width: 200px">
          <div id="range"></div>
          <div id='slider-range'></div>
          <input name='min' id='min' type='hidden' value="<?=$min?>"/>
          <input name='max' id='max' type='hidden' value="<?=$max?>"/>
      </tr>
      <tr>
        <th>% of Answers to Hide</th>
        <td>
          <div id="skip_label"></div>
          <div id='slider-skip'></div>
          <input name='skip' id='skip' type='hidden' value="<?=$skip?>"/>
        </td>
      </tr>
      <tr>
        <th> </th>
        <td><input type="submit" value="Go"/></td>
      </tr>
    </table>
  </form>
  <div class="page key">
    <h1><?=$ops[$op]?> Solver Answer Key</h1>
    <h3><?=date('m/d/Y')?></h3>
    <h3>Answer Key: <?=$key?></h3>
    <table cellspacing=0>
      <tr>
        <? foreach($table as $row): ?>
          <tr>
            <? foreach($row as $field): ?>
              <td><?= $field?></td>
            <? endforeach;?>
          </tr>
        <? endforeach; ?>
      </tr>
    </table>
  </div>
  <div class="page" style="page-break-before: always">
    <h1><?=$ops[$op]?> Solver</h1>
    <h3><?=date('m/d/Y')?></h3>
    <table cellspacing=0>
      <tr>
        <? foreach($table as $row): ?>
          <tr>
            <? foreach($row as $field): ?>
              <td><?= $field?></td>
            <? endforeach;?>
          </tr>
        <? endforeach; ?>
      </tr>
    </table>
    Key: <?=$key?>
  </div>
</body>
</html>