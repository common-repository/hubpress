<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>
<h2>LOG</h2>
<?php if(!empty($lines))
{
   foreach($lines as $line)
   {
       if(trim($line)!='')
       {
           $ex = explode(']',$line);
           $time = str_replace('[', '', $ex[0]);
           echo '<p><b>'.date('m/d/Y h:i:sa').'</b> '.$ex[1].'</p>';
       }
   }
}?>
