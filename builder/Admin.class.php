<?php
namespace Core\Builder;

class Admin extends \Core\baseGenerator {

  private $name = null;
  private $fields = null;
  private $location = null;

  public function __construct($name = null,$fields = null) {
    $this->name = $name;
    $this->fields = $fields;
    $this->location = \Core\Config::path("shared")."/objects/admin";
  }

  public function setName($name) {
    $this->name = $name;
  }

  public function getName() {
    return $this->name;
  }

  public function setFields($fields) {
    $this->fields = $fields;
  }

  public function getFields() {
    return $this->fields;
  }

  public function generate() {
  //admin module is not generated with a base and normal
  //it's a fully made module, and could be copied to applications if needed
    if(file_exists($this->location.'/'.$this->name)) {
      \Core\Tools::deleteFolder($this->location.'/'.$this->name);
    }
    //folder does not exists, so ok to proceed
    //create module folder
    mkdir($this->location.'/'.$this->name);
    //create module actions folder
    mkdir($this->location.'/'.$this->name."/actions");
    //create module templates folder
    mkdir($this->location.'/'.$this->name."/templates");
    //create module config folder
    mkdir($this->location.'/'.$this->name."/config");
    $this->createActions();
    $this->createTemplates();
    $this->createConfig();

  }

  private function createActions() {
    $file = fopen($this->location."/".$this->name."/actions/actions.class.php", "w");
    fwrite($file,"<?php\n");
    fwrite($file,"\n");
    fwrite($file,"class ".$this->name."Actions extends Actions{\n");
    fwrite($file,"\t//put your code here\n");
    fwrite($file,"\tpublic function index(){\n");
    fwrite($file,"\t\tif(Request::Method() == 'POST') \$this->forward('save');\n");
    fwrite($file,"\t\t\$this->page = Request::getGetParameter('p',1);\n");
    fwrite($file,"\t\t\$this->collection = &Database::Find('".$this->name."')->all(\$this->page,20);\n");
    fwrite($file,"\t\t\$this->pages = Database::Find('".$this->name."')->getPages();\n");
    fwrite($file,"\t}\n");
    fwrite($file,"\n");
    fwrite($file,"\tpublic function save(){\n");
    fwrite($file,"\t\t\$".strtolower($this->name)." = new ".$this->name."();\n");
    fwrite($file,"\t\t\$".strtolower($this->name)."->fromArray(Request::getPostParameter('".strtolower($this->name)."',array()));\n");
    fwrite($file,"\t\tif($".strtolower($this->name)."->getID() != 0) $".strtolower($this->name)."->state(DataLayer::STATE_LOADED);\n");
    fwrite($file,"\t\tif($".strtolower($this->name)."->persist()){\n");
    fwrite($file,"\t\t\tSession::addFlash('notice','".$this->name." has successfully been added to the database.');\n");
    fwrite($file,"\t\t}else{\n");
    fwrite($file,"\t\t\tSession::addFlash('error','".$this->name." could not be added to the database.');\n");
    fwrite($file,"\t\t}\n");
    fwrite($file,"\t}\n");
    fwrite($file,"\tpublic function delete(){\n");
    fwrite($file,"\t\t\$".strtolower($this->name)." = &Database::Find('".$this->name."')->byID(Request::getGetParameter('id'));\n");
    fwrite($file,"\t\tif(!\$".strtolower($this->name)." instanceOf ".$this->name."){\n");
    fwrite($file,"\t\t\tSession::addFlash('error','".$this->name." does not exist.');\n");
    fwrite($file,"\t\t}else{\n");
    fwrite($file,"\t\t\tif(\$".strtolower($this->name)."->delete()){\n");
    fwrite($file,"\t\t\t\tSession::addFlash('notice','".$this->name." is successfully deleted.');\n");
    fwrite($file,"\t\t\t}else{\n");
    fwrite($file,"\t\t\t\tSession::addFlash('error','".$this->name." could not be deleted.');\n");
    fwrite($file,"\t\t\t}\n");
    fwrite($file,"\t\t}\n");
    fwrite($file,"\t}\n");
    fwrite($file,"\n");
    fwrite($file,"}\n");
    fwrite($file,"?>");
    fclose($file);
    chmod($this->location."/".$this->name."/actions/actions.class.php",0777);
  }

  private function createTemplates() {
    $file = fopen($this->location."/".$this->name."/templates/index.template.php", "w");
    fwrite($file,"<div class=\"clearfix\" id=\"admin\">\n");
    fwrite($file,"\t<div class=\"table\" style=\"width:400px;\">\n");
    fwrite($file,"\t\t<form name=\"frmManageData\" method=\"post\" action=\"\">\n");
    fwrite($file,"\t\t\t<h2>".$this->name." Entry</h2>");
    foreach($this->fields as $field) {
      fwrite($file,"\t\t\t<div class=\"clearfix\">\n");
      fwrite($file,"\t\t\t\t<div class=\"fieldname\">".$field["name"].":</div>\n");
      switch($field["type"]) {
        case "integer":
          /*if(substr($field["name"],-2) == "ID" && $field["name"] != "ID"){ //foreign key
            fwrite($file,"\t\t\t\t<div class=\"fieldinput\">\n");
            fwrite($file,"\t\t\t\t\t<select id=\"".strtolower($this->name."_".$field["name"])."\" name=\"".strtolower($this->name)."[".$field["name"]."]\">\n");
            fwrite($file,"\t\t\t\t\t\t<?php foreach(Database::Find(\"".substr($field["name"],0,-2)."\")->all() as \$entry): ?>\n");
            fwrite($file,"\t\t\t\t\t\t<option value=\"<?php echo \$entry->getID(); ?>\"><?php echo \$entry; ?></option>\n");
            fwrite($file,"\t\t\t\t\t\t<?php endforeach; ?>\n");
            fwrite($file,"\t\t\t\t\t</select>\n");
            fwrite($file,"\t\t\t\t</div>\n");
          }
          else{*/
          fwrite($file,"\t\t\t\t<div class=\"fieldinput\"><input type=\"text\" id=\"".strtolower($field["name"])."\" name=\"".strtolower($this->name)."[".$field["name"]."]\" /></div>\n");
          //}
          break;
        case "string":
          if($field["length"] == 0) {
            fwrite($file,"\t\t\t\t<div class=\"fieldtextarea\"><textarea id=\"".strtolower($field["name"])."\" name=\"".strtolower($this->name)."[".$field["name"]."]\" cols=\"45\" rows=\"5\"></textarea></div>\n");
          }
          else {
            fwrite($file,"\t\t\t\t<div class=\"fieldinput\"><input type=\"text\" id=\"".strtolower($field["name"])."\" name=\"".strtolower($this->name)."[".$field["name"]."]\" /></div>\n");
          }
          break;
        default:
          fwrite($file,"\t\t\t\t<div class=\"fieldinput\"><input type=\"text\" id=\"".strtolower($field["name"])."\" name=\"".strtolower($this->name)."[".$field["name"]."]\" /></div>\n");
          break;
      }
      fwrite($file,"\t\t\t</div>\n");
    }
    fwrite($file,"\t\t\t<div class=\"button\">\n");
    fwrite($file,"\t\t\t\t<input type=\"submit\" value=\"Save\" name=\"btnSave\" />\n");
    fwrite($file,"\t\t\t\t<input type=\"button\" value=\"Reset\" name=\"btnReset\" />\n");
    fwrite($file,"\t\t\t</div>\n");
    fwrite($file,"\t\t</form>\n");
    fwrite($file,"\t</div>\n");
    fwrite($file,"\t<div class=\"table\">\n");
    fwrite($file,"\t\t<h2>".$this->name." List</h2>\n");
    fwrite($file,"\t\t<table cellspacing=\"0\" cellpadding=\"0\">\n");
    fwrite($file,"\t\t\t<tr>\n");
    foreach($this->fields as $field) {
      fwrite($file,"\t\t\t\t<th>".$field["name"]."</th>\n");
    }
    fwrite($file,"\t\t\t\t<th>&nbsp;</th>\n");
    fwrite($file,"\t\t\t</tr>\n");
    fwrite($file,"\t\t\t<?php\n");
    fwrite($file,"\t\t\t\$i = 0;\n");
    fwrite($file,"\t\t\tif(count(\$collection) > 0) {\n");
    fwrite($file,"\t\t\t\tforeach(\$collection as \$entry) {\n");
    fwrite($file,"\t\t\t\t\t?>\n");
    fwrite($file,"\t\t\t<tr class=\"mailRow <?php echo \$i++%2?\"odd\":\"even\"; ?>\">\n");
    foreach($this->fields as $field) {
      fwrite($file,"\t\t\t\t<td title=\"".$field["name"]."\"><?php echo \$entry->get".Tools::strtocamelcase($field["name"],true)."(); ?></td>\n");
    }
    fwrite($file,"\t\t\t\t<td>\n");
    fwrite($file,"\t\t\t\t\t<div class=\"actionContainer\">\n");
    fwrite($file,"\t\t\t\t\t\t<a href=\"javascript:;\" class=\"admin_icon edit\" title=\"Click here to edit this record\">edit</a>\n");
    fwrite($file,"\t\t\t\t\t\t<a href=\"<?php echo Route::url(Route::curr_mod().'/'.Route::curr_act(), array('o' => Request::getGetParameter('o'), 'del' => \$entry->getID())); ?>\" class=\"admin_icon del\" title=\"Click here to delete this record\">delete</a>\n");
    fwrite($file,"\t\t\t\t\t</div>\n");
    fwrite($file,"\t\t\t\t</td>\n");
    fwrite($file,"\t\t\t</tr>\n");
    fwrite($file,"\t\t\t\t<?php\n");
    fwrite($file,"\t\t\t\t}\n");
    fwrite($file,"\t\t\t}else {\n");
    fwrite($file,"\t\t\t\t?>\n");
    fwrite($file,"\t\t\t<tr><td colspan=\"".(count($this->fields) + 1)."\">No Records.</tr>\n");
    fwrite($file,"\t\t\t<?php\n");
    fwrite($file,"\t\t\t}\n");
    fwrite($file,"\t\t\t?>\n");
    fwrite($file,"\t\t\t<tr><td colspan=\"".(count($this->fields) + 1)."\" class=\"navigation\"><?php echo Pager::createNavigation(\"manage/data\", \$page, \$pages, array('o' => '".$this->name."')); ?></td></tr>\n");
    fwrite($file,"\t\t</table>\n");
    fwrite($file,"\t</div>\n");
    fwrite($file,"</div>\n");
    fclose($file);
    chmod($this->location."/".$this->name."/templates/index.template.php",0777);
  }

  private function createConfig() {
    $file = fopen($this->location.'/'.$this->name."/config/view.yml","w");
    fwrite($file,"javascript:\n");
    fwrite($file,"  -admin.js\n");
    fwrite($file,"stylesheet:\n");
    fwrite($file,"  -admin.css\n");
    fclose($file);
    chmod($this->location.'/'.$this->name."/config/view.yml",0777);
  }

  public function __destroy() {
    unset($this->name,$this->fields,$this->location);
  }

}