<?php
set_time_limit(3600);

function Debug($file, $line, $message) {
  file_put_contents("sqlot.log", "[".date('Y/m/d H:i:s')."]".$file."(".strval($line)."): ".$message."<EOL>".PHP_EOL, FILE_APPEND);
}

class SQLotSpecs {
  public static $code_name = "SQLot";
  public static $version = "0.0.22";
  public static $copyright = "Copyright (C) KimuraShinichi 2012,2013";
  public static $licenced_by = "Apache Licence 2.0";
  public static $valid_upload_file_extension = "(sql|txt)";
}
class SQLot {
  private $textBundle = null;
  public function __construct() {
  }
  public function show_page($request){
    try {
      list($request,$error,$resultSet) = $this->control($request);
    } catch (Exception $e) {
      list($error,$resultSet) = array($e->getMessage(),null);
    }
    $this->view($request,$error,$resultSet);
  }
  protected function control($request){
    $error = null;
    $resultSet = array();
    $request["language"] = ($this->is_missing($request,"language")) ? "ja" : $request["language"];
    $this->textBundle = new SQLotTextBundle($request["language"]);
    if ($this->is_missing($request,"dsn")) {
      $error = $this->text("error_dsn_missing");
      return array($request,$error,$resultSet);
    }
    if ($this->value_equals("true", $request,"need-pretty")) {
      $pretty = new SQLotPrettySQL();
      $request["sql"] = $pretty->pretty($request["sql"]);
      return array($request,$error,$resultSet);
    }
    $upload = new SQLotFileUploader($this->textBundle);
    $upload->set_file_id("upload");
    if ($upload->is_uploaded_file_specified()) {
      if ("" === $upload->check_upload_error()) {
        $request["sql"] = file_get_contents($upload->uploaded_file_path());
      }
      return array($request,$error,$resultSet);
    } else {
      if ($this->is_missing($request,"sql")) {
        $error = $this->text("error_sql_missing");
        return array($request,$error,$resultSet);
      }
      $db = new SQLotDBAccessor($request,$this->textBundle);
      $db->begin();
      list($error,$resultSet) = $db->run($request["sql"]);
      $db->end();
    }
    return array($request,$error,$resultSet);
  }
  protected function view($request,$error,$resultSet) {
    $view = new SQLotHTMLView($this->textBundle);
    $view->write_html($request,$error,$resultSet);
  }
  private function text($key) {
    return $this->textBundle->text($key);
  }
  private function is_missing($request,$key) {
    return (!isset($request[$key])) || $this->is_empty($request[$key]);
  }
  private function is_empty($v) {
    return SQLotUtil::is_empty($v);
  }
  private function value_equals($expected,$request,$key) {
    if ($this->is_missing($request,$key)) {
      return false;
    }
    return $expected === $request[$key];
  }
}
class Utf8 {
//TEST//	public function test_charAt() {
//TEST//		$this->assertEquals("a", Utf8::charAt(0, "aあ1２"));
//TEST//		$this->assertEquals("あ", Utf8::charAt(1, "aあ1２"));
//TEST//		$this->assertEquals("1", Utf8::charAt(2, "aあ1２"));
//TEST//		$this->assertEquals("２", Utf8::charAt(3, "aあ1２"));
//TEST//	}
  public static function charAt($num, $text) {
    return mb_substr($text, $num, 1, 'UTF-8');
  }
//TEST//	public function test_strlen() {
//TEST//		$this->assertEquals("4", Utf8::strlen("aあ1２"));
//TEST//		$this->assertEquals("6", Utf8::strlen("abc123"));
//TEST//		$this->assertEquals("6", Utf8::strlen("あいう１２３"));
//TEST//	}
  public static function strlen($str) {
    return mb_strlen($str, 'UTF-8');
  }
//TEST//	public function test_strtoupper() {
//TEST//		$this->assertEquals("Aあ1２", Utf8::strtoupper("aあ1２"));
//TEST//		$this->assertEquals("ALPHABET", Utf8::strtoupper("alphabet"));
//TEST//		$this->assertEquals("あいうえお", Utf8::strtoupper("あいうえお"));
//TEST//	}
  public static function strtoupper($str) {
    return mb_strtoupper($str, 'UTF-8');
  }
//TEST//	public function test_substr() {
//TEST//		$this->assertEquals("a", Utf8::substr("aあ1２", 0,1));
//TEST//		$this->assertEquals("あ1", Utf8::substr("aあ1２", 1,2));
//TEST//		$this->assertEquals("あ1２", Utf8::substr("aあ1２", 1,3));
//TEST//		$this->assertEquals("1", Utf8::substr("aあ1２", 2,1));
//TEST//		$this->assertEquals("２", Utf8::substr("aあ1２", 3,1));
//TEST//	}
  public static function substr($str, $start, $length) {
    return mb_substr($str, $start, $length, 'UTF-8');
  }
//TEST//	public function test_replace() {
//TEST//		$this->assertEquals("a\nb\rc\n\nd\n", Utf8::replace("[\r][\n]", "\n", "a\r\nb\rc\r\n\r\nd\r\n"));
//TEST//	}
  public static function replace($pattern, $replacement, $string) {
    mb_regex_encoding('UTF-8');
    return mb_ereg_replace($pattern, $replacement, $string);
  }
//TEST//	public function test_match() {
//TEST//		$this->assertEquals(true, Utf8::match("^///", "///abcdefあいうえお"));
//TEST//		$this->assertEquals(false, Utf8::match("^///", "//abcdefあいうえお"));
//TEST//	}
  public static function match($pattern, $string) {
    mb_regex_encoding('UTF-8');
    return mb_ereg_match($pattern, $string);
  }
//TEST//	public function test_split() {
//TEST//		$this->assertEquals(array("a", "b", "c", "d", "e", "f", "g"), Utf8::split("[ \t]+", "a\tb c \td\t e  f\t\tg"));
//TEST//	}
  public static function split($pattern, $string) {
    mb_regex_encoding('UTF-8');
    return mb_split($pattern, $string);
  }
}
class SQLotPrettySQL {
//TEST//	public function setUp() {
//TEST//		$this->fixture = new SQLotPrettySQL();
//TEST//	}
//TEST//	public function test_pretty_space() {
//TEST//		$this->assertEquals(null, $this->fixture->pretty(null));
//TEST//		$this->assertEquals("", $this->fixture->pretty(""));
//TEST//		$this->assertEquals("", $this->fixture->pretty(" "));
//TEST//		$this->assertEquals("", $this->fixture->pretty("\t"));
//TEST//		$this->assertEquals("", $this->fixture->pretty("\n"));
//TEST//		$this->assertEquals("", $this->fixture->pretty("\r\n"));
//TEST//	}
//TEST//	public function test_pretty_a_space_b() {
//TEST//		$this->assertEquals("a b", $this->fixture->pretty("a b"));
//TEST//		$this->assertEquals("a b", $this->fixture->pretty("a\tb"));
//TEST//		$this->assertEquals("a b", $this->fixture->pretty("a\nb"));
//TEST//		$this->assertEquals("a b", $this->fixture->pretty("a\r\nb"));
//TEST//	}
//TEST//	public function test_pretty_a_spaces_b() {
//TEST//		$this->assertEquals("a b", $this->fixture->pretty("a \t  \t\t   \n   \r\nb"));
//TEST//		$this->assertEquals("' \t  \t\t   \n   \n'", $this->fixture->pretty("' \t  \t\t   \n   \r\n'"));//[\r\n]は[\n]に置換される。
//TEST//	}
//TEST//	public function test_pretty_numbers() {
//TEST//		$this->assertEquals("1", $this->fixture->pretty("1"));
//TEST//		$this->assertEquals("1,2", $this->fixture->pretty("1,2"));
//TEST//		$this->assertEquals("1,2, 3, 4, 5", $this->fixture->pretty("1,2, 3,  4,   5"));
//TEST//		$this->assertEquals("'1,2, 3,  4,   5'", $this->fixture->pretty("'1,2, 3,  4,   5'"));
//TEST//	}
//TEST//	public function test_pretty_select() {
//TEST//		$this->assertEquals("select a".PHP_EOL."from table1", $this->fixture->pretty("select a from table1"));
//TEST//		$this->assertEquals("select a".PHP_EOL."from table1".PHP_EOL."where a=1",
//TEST//			$this->fixture->pretty("select a from table1 where a=1"));
//TEST//		$this->assertEquals("select a, b".PHP_EOL."from table1".PHP_EOL."where a=1".PHP_EOL."group by b",
//TEST//			$this->fixture->pretty("select a, b from table1 where a=1 group by b"));
//TEST//		$this->assertEquals(
//TEST//				"select a, b, c".PHP_EOL."from table1".PHP_EOL.
//TEST//				"where a=1".PHP_EOL."group by b".PHP_EOL."order by c",
//TEST//			$this->fixture->pretty("select a, b, c from table1 where a=1 group by b order by c"));
//TEST//	}
//TEST//	public function test_pretty_multiple_sql() {
//TEST//		$this->assertEquals(
//TEST//				"select a".PHP_EOL.
//TEST//				"from table1;".PHP_EOL.
//TEST//				"select b".PHP_EOL.
//TEST//				"from table2;",
//TEST//			$this->fixture->pretty("select a from table1;select b from table2;"));
//TEST//		$this->assertEquals(
//TEST//				"select a".PHP_EOL.
//TEST//				"from table1;".PHP_EOL.
//TEST//				"--select b from table2;".PHP_EOL.
//TEST//				"select c".PHP_EOL.
//TEST//				"from table3;",
//TEST//			$this->fixture->pretty("select a from table1;--select b from table2;\nselect c from table3;"));
//TEST//		$this->assertEquals(
//TEST//				"--select a from table1;".PHP_EOL.
//TEST//				"select b".PHP_EOL.
//TEST//				"from table2;".PHP_EOL.
//TEST//				"--select c from table3;",
//TEST//			$this->fixture->pretty("--select a from table1;\nselect b from table2;\n--select c from table3;"));
//TEST//	}
//TEST//	public function test_pretty_select_nested() {
//TEST//		$this->assertEquals(
//TEST//				"select a, b, c".PHP_EOL.
//TEST//				"from".PHP_EOL.
//TEST//				"(".PHP_EOL.
//TEST//				$this->fixture->indent(1)."select a, b, c".PHP_EOL.
//TEST//				$this->fixture->indent(1)."from table1".PHP_EOL.
//TEST//				") as t".PHP_EOL.
//TEST//				"where a=1".PHP_EOL.
//TEST//				"group by b".PHP_EOL.
//TEST//				"order by c",
//TEST//			$this->fixture->pretty(
//TEST//				"select a, b, c from (select a, b, c from table1) as t where a=1 group by b order by c")
//TEST//		);
//TEST//	}
//TEST//	public function test_pretty_select_nested_double() {
//TEST//		$this->assertEquals(
//TEST//				"select a, b, c".PHP_EOL.
//TEST//				"from".PHP_EOL.
//TEST//				"(".PHP_EOL.
//TEST//				$this->fixture->indent(1)."select a, b, c".PHP_EOL.
//TEST//				$this->fixture->indent(1)."from".PHP_EOL.
//TEST//				$this->fixture->indent(1)."(".PHP_EOL.
//TEST//				$this->fixture->indent(2)."select a, b, c".PHP_EOL.
//TEST//				$this->fixture->indent(2)."from table1".PHP_EOL.
//TEST//				$this->fixture->indent(1).") as t2".PHP_EOL.
//TEST//				$this->fixture->indent(1)."where a=1".PHP_EOL.
//TEST//				") as t".PHP_EOL.
//TEST//				"where a=1",
//TEST//			$this->fixture->pretty(
//TEST//				"select a, b, c from (select a, b, c from (select a, b, c from table1) as t2 where a=1) as t where a=1")
//TEST//		);
//TEST//	}
//TEST//	public function test_pretty_comment() {
//TEST//		$this->assertEquals(
//TEST//				"select a, b, c".PHP_EOL.
//TEST//				"from".PHP_EOL.
//TEST//				"(".PHP_EOL.
//TEST//				$this->fixture->indent(1)."select a, b, c".PHP_EOL.
//TEST//				$this->fixture->indent(1)."from".PHP_EOL.
//TEST//				$this->fixture->indent(1)."(".PHP_EOL.
//TEST//				$this->fixture->indent(2)."select a, b--コメント".PHP_EOL.
//TEST//				$this->fixture->indent(2).", c".PHP_EOL.
//TEST//				$this->fixture->indent(2)."from table1".PHP_EOL.
//TEST//				$this->fixture->indent(1).") as t2".PHP_EOL.
//TEST//				$this->fixture->indent(1)."where a=1".PHP_EOL.
//TEST//				") as t".PHP_EOL.
//TEST//				"where a=1",
//TEST//			$this->fixture->pretty(
//TEST//				"select a, b, c from (select a, b, c from (select a, b--コメント\r\n, c from table1) as t2 where a=1) as t where a=1")
//TEST//		);
//TEST//	}
  public function pretty($sql) {
    mb_regex_encoding('UTF-8');
    $tokens = $this->tokenize($sql);
    $result = "";
    $depth = 0;
    foreach ($tokens as $token) {
      if ($this->should_start_newline($token)) {
        $result = trim($result);
        if (")" === $token) { --$depth; }
        $result .= PHP_EOL.$this->indent($depth);
        if ("(" === $token) { ++$depth; }
      }
      if ($this->is_comment($token)) {
        $result .= $token;
        $result .= $this->indent($depth);
      } else {
        $lenEOL = Utf8::strlen(PHP_EOL);
        if (PHP_EOL === Utf8::substr($result, Utf8::strlen($result) - $lenEOL, $lenEOL) && " " === $token) {
          $token = "";
        }
        $result .= $token;
      }
      if ($this->is_statement_delimiter($token)) {
        $result .= PHP_EOL;
        $depth = 0;
      }
    }
    $result = trim($result);
    return $result;
  }
//TEST//	public function test_indent() {
//TEST//		$this->assertEquals("", $this->fixture->indent(0));
//TEST//		$this->assertEquals("  ", $this->fixture->indent(1));
//TEST//		$this->assertEquals("    ", $this->fixture->indent(2));
//TEST//	}
  public function indent($depth) {
    $indent = "";
    for ($i = 0; $i < $depth; $i += 1) {
      $indent .= "  ";
    }
    return $indent;
  }
  public function should_start_newline($token) {
    $new_line_before = "( ) SELECT FROM WHERE GROUP ORDER LEFT RIGHT JOIN UNION ON";
    $keywords = explode(" ", $new_line_before);
    foreach ($keywords as $key) {
      $upper = Utf8::strtoupper($token);
      if ($key === $upper) { return true; }
    }
    return false;
  }
//TEST//	public function test_is_comment() {
//TEST//		$this->assertEquals(true, $this->fixture->is_comment("--コメント\n"));
//TEST//		$this->assertEquals(false, $this->fixture->is_comment("-コメント\n"));
//TEST//	}
  public function is_comment($token) {
    return ("--" === Utf8::substr($token, 0, 2));
  }
//TEST//	public function test_is_statement_delimiter() {
//TEST//		$this->assertEquals(true, $this->fixture->is_statement_delimiter(";"));
//TEST//		$this->assertEquals(false, $this->fixture->is_statement_delimiter(":"));
//TEST//		$this->assertEquals(false, $this->fixture->is_statement_delimiter(";\n"));
//TEST//	}
  public function is_statement_delimiter($token) {
    return (";" === $token);
  }
//TEST//	public function test_is_eol_char() {
//TEST//		$this->assertEquals(false, $this->fixture->is_eol_char(" "));
//TEST//		$this->assertEquals(false, $this->fixture->is_eol_char("\t"));
//TEST//		$this->assertEquals(true, $this->fixture->is_eol_char("\n"));
//TEST//		$this->assertEquals(true, $this->fixture->is_eol_char("\r"));
//TEST//		$this->assertEquals(false, $this->fixture->is_eol_char("'"));
//TEST//		$this->assertEquals(false, $this->fixture->is_eol_char("a"));
//TEST//	}
  public function is_eol_char($char) {
    if ("\r" === $char) { return true; }
    if ("\n" === $char) { return true; }
    return false;
  }
//TEST//	public function test_is_space_char() {
//TEST//		$this->assertEquals(true, $this->fixture->is_space_char(" "));
//TEST//		$this->assertEquals(true, $this->fixture->is_space_char("\t"));
//TEST//		$this->assertEquals(true, $this->fixture->is_space_char("\n"));
//TEST//		$this->assertEquals(true, $this->fixture->is_space_char("\r"));
//TEST//		$this->assertEquals(false, $this->fixture->is_space_char("'"));
//TEST//		$this->assertEquals(false, $this->fixture->is_space_char("a"));
//TEST//	}
  public function is_space_char($char) {
    if (" " === $char) { return true; }
    if ("\t" === $char) { return true; }
    if ("\r" === $char) { return true; }
    if ("\n" === $char) { return true; }
    return false;
  }
//TEST//	public function test_char_type() {
//TEST//		$this->assertEquals("SPACE", $this->fixture->char_type(" "));
//TEST//		$this->assertEquals("SPACE", $this->fixture->char_type("\t"));
//TEST//		$this->assertEquals("EOL", $this->fixture->char_type("\n"));
//TEST//		$this->assertEquals("EOL", $this->fixture->char_type("\r"));
//TEST//		$this->assertEquals("-", $this->fixture->char_type("-"));
//TEST//		$this->assertEquals("QUOT", $this->fixture->char_type("'"));
//TEST//		$this->assertEquals("(", $this->fixture->char_type("("));
//TEST//		$this->assertEquals(")", $this->fixture->char_type(")"));
//TEST//		$this->assertEquals("ELSE", $this->fixture->char_type("a"));
//TEST//		$this->assertEquals("ELSE", $this->fixture->char_type("?"));
//TEST//	}
  public function char_type($char) {
    if (";" === $char) { return "EOS"; }
    if ("'" === $char) { return "QUOT"; }
    if ("(" === $char) { return "("; }
    if (")" === $char) { return ")"; }
    if ("-" === $char) { return "-"; }
    if ($this->is_eol_char($char)) { return "EOL"; }
    if ($this->is_space_char($char)) { return "SPACE"; }
    return "ELSE";
  }
  public function append_token_if_not_empty($tokens, $token) {
    if ("" !== $token) {
      $tokens[] = $token;
    }
    return $tokens;
  }
//TEST//	public function test_tokenize() {
//TEST//		$this->assertEquals(array(
//TEST//			"@",
//TEST//			" ",
//TEST//			"@@",
//TEST//			" ",
//TEST//			"@@@",
//TEST//			" ",
//TEST//			"@@",
//TEST//			" ",
//TEST//			"@",
//TEST//			" ",
//TEST//			"@@@"), $this->fixture->tokenize("@ @@ @@@ @@ @ @@@"));
//TEST//		$this->assertEquals(array(
//TEST//			"@",
//TEST//			" ",
//TEST//			"@@",
//TEST//			" ",
//TEST//			"'@@@ @'",
//TEST//			"@",
//TEST//			" ",
//TEST//			"@",
//TEST//			" ",
//TEST//			"@@@"), $this->fixture->tokenize("@ @@ '@@@ @'@ @ @@@"));
//TEST//	}
  public function tokenize($sql) {
    $states = array (
      "inQuote"    => array("QUOT" => ".';", "SPACE" => ".?.", "EOL" => ".?.", "(" => ",?,", ")" => ",?,", "-" => ".?.", "EOS" => ",?!", "ELSE" => ".?."),
      "outOfQuote" => array("QUOT" => ";'.", "SPACE" => "+ !", "EOL" => "+ !", "(" => ",?,", ")" => ",?,", "-" => ".?/", "EOS" => ",?!", "ELSE" => ".?."),
      "toComment"  => array("QUOT" => ";'.", "SPACE" => "+ !", "EOL" => "+ !", "(" => ",?,", ")" => ",?,", "-" => "--{", "EOS" => ",?!", "ELSE" => ".?."),
      "inComment"  => array("QUOT" => ".?.", "SPACE" => ".?.", "EOL" => ".?}", "(" => ".?.", ")" => ".?.", "-" => ".?.", "EOS" => ".?.", "ELSE" => ".?.")
    );
    $in_quote = "outOfQuote";
    $tokens = array();
    $token = "";
    $sql = Utf8::replace("[\r][\n]", "\n", $sql);
    for ($i = 0; $i < Utf8::strlen($sql); $i += 1) {
      $char = Utf8::charAt($i, $sql);
      $char_type = $this->char_type($char);
      $state = $states[$in_quote][$char_type];
      if (".';" === $state) {
        $tokens[] = $token."'";
        $in_quote = "outOfQuote";
        $token = "";
      } else if (".?." === $state) {
        $token .= $char;
      } else if (";'." === $state) {
        $tokens = $this->append_token_if_not_empty($tokens, $token);
        $in_quote = "inQuote";
        $token = "'";
      } else if ("+ !" === $state) {
        $tokens = $this->append_token_if_not_empty($tokens, $token);
        $last_token = (0 === count($tokens)) ? "" : $tokens[count($tokens) - 1];
        if (" " === $last_token) {
          $token = "";
        } else {
          $tokens = $this->append_token_if_not_empty($tokens, " ");
        }
        $in_quote = "outOfQuote";
        $token = "";
      } else if (",?," === $state) {
        $tokens = $this->append_token_if_not_empty($tokens, $token);
        $tokens = $this->append_token_if_not_empty($tokens, $char);
        $token = "";
      } else if (",?!" === $state) {
        $tokens = $this->append_token_if_not_empty($tokens, $token);
        $tokens = $this->append_token_if_not_empty($tokens, $char);
        $token = "";
        $in_quote = "outOfQuote";
      } else if (".?/" === $state) {
        $token .= $char;
        $in_quote = "toComment";
      } else if ("--{" === $state) {
        $token = Utf8::substr($token, 0, Utf8::strlen($token) - 1);//末尾1文字(-)を削除。
        $tokens = $this->append_token_if_not_empty($tokens, $token);
        $in_quote = "inComment";
        $token = "--";//コメント開始。
      } else if (".?}" === $state) {
        $token .= PHP_EOL;
        $tokens = $this->append_token_if_not_empty($tokens, $token);//コメント終了。
        $in_quote = "outOfQuote";
        $token = "";
      }
    }
    if (0 < Utf8::strlen($token)) {
      $tokens[] = $token;
    }
    return $tokens;
  }
}
class SQLotFileUploader {
  private $textBundle;
  private $id;
  public function __construct($textBundle) {
    $this->textBundle = $textBundle;
  }
  public function set_file_id($id) {
    $this->id = $id;
  }
  public function uploaded_file_path() {
    return $_FILES[$this->id]["tmp_name"];
  }
  public function is_uploaded_file_specified() {
    if (isset($_FILES)) {
      if (isset($_FILES[$this->id]["error"])) {
        if (UPLOAD_ERR_NO_FILE != $_FILES[$this->id]["error"]) {
          return true;
        }
      }
    }
    return false;
  }
  public function check_upload_error() {
    $error = "";
    if ($this->is_uploaded_file_too_large()) {
      return $this->text("error_file_too_large");
    }
    if ($this->has_uploaded_file_error()) {
      return $this->get_upload_error_text();
    }
    if (!$this->is_uploaded_file_text()) {
      return $this->text("error_file_not_text")."[name=".$_FILES[$this->id]["name"]."]"."[type=".strval($_FILES[$this->id]["type"])."]";
    }
    if (!$this->is_uploaded_file_extension_valid()) {
      return $this->text("error_file_extension_invalid")."[name=".$_FILES[$this->id]["name"]."]";
    }
    return $error;
  }
  protected function get_upload_error_text() {
    switch ($_FILES[$this->id]["error"]) {
    case UPLOAD_ERR_INI_SIZE: $error = $this->text("UPLOAD_ERR_INI_SIZE"); break;
    case UPLOAD_ERR_FORM_SIZE: $error = $this->text("UPLOAD_ERR_FORM_SIZE"); break;
    case UPLOAD_ERR_PARTIAL: $error = $this->text("UPLOAD_ERR_PARTIAL"); break;
    case UPLOAD_ERR_NO_FILE: $error = $this->text("UPLOAD_ERR_NO_FILE"); break;
    case UPLOAD_ERR_NO_TMP_DIR: $error = $this->text("UPLOAD_ERR_NO_TMP_DIR"); break;
    case UPLOAD_ERR_CANT_WRITE: $error = $this->text("UPLOAD_ERR_CANT_WRITE"); break;
    case UPLOAD_ERR_EXTENSION: $error = $this->text("UPLOAD_ERR_EXTENSION"); break;
    default: $error = strval($_FILES[$this->id]["error"]); break;
    }
    return $this->text("error_file_upload_failed")."[name=".$_FILES[$this->id]["name"]."]"."[error=".$error."]";
  }
  protected function text($key) {
    return $this->textBundle->text($key);
  }
  protected function is_uploaded_file_too_large() {
    return (isset($_FILES) && null == $_FILES);
  }
  protected function has_uploaded_file_error() {
    return !(isset($_FILES) && UPLOAD_ERR_OK == $_FILES[$this->id]["error"]);
  }
  protected function is_uploaded_file_text() {
    return (isset($_FILES) && preg_match("%^(text/.*|application/octet-stream)$%",$_FILES[$this->id]["type"]));
  }
  protected function is_uploaded_file_extension_valid() {
    $specs = new SQLotSpecs();
    return (isset($_FILES) && preg_match("%[.]".$this->text("valid_upload_file_extension")."$%",$_FILES[$this->id]["name"]));
  }
}
class SQLotDBAccessor {
  protected $pdo;
  protected $pdo_exception_list;
  private $request;
  private $textBundle;
  public function __construct($request,$textBundle) {
    $this->request = $request;
    $this->textBundle = $textBundle;
  }
  public function text($key) {
    return $this->textBundle->text($key);
  }
  public function begin() {
    $this->pdo = $this->create_PDO($this->request);
    $pdo_exception_list = array();
  }
  public function end() {
    $this->pdo = null;
    $pdo_exception = null;
  }
  public function run($sql_statements) {
    $error = null;
    $sql = '';
    $resultSet = array();
    try {
      $sqls = $this->remove_SQL_comment($sql_statements);
      foreach (preg_split('/;/', $sqls) as $sql) {
        $resultSet = $this->run_sql($sql, array(), $resultSet);
      }
    } catch (PDOException $e) {
      $error = $this->text("pdo_exception_prefix").$e->getMessage()."[sql=".$this->get_SQL_as_html_table($sql)."]";
    }
    return array($error,$resultSet);
  }
  protected function create_PDO($request) {
    $pdo = new PDO($request["dsn"],$request["db_user"],$request["db_password"]);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
  }
  protected function catch_and_release_pdo_exception($exception) {
    $this->pdo_exception_list[] = $exception;
    return $exception;
  }
  protected function run_sql($sql, $params, $resultSet) {
    $sql = trim($sql);
    if ($this->is_empty($sql)) {
      return $resultSet;
    }
    try {
        $statement = $this->pdo->prepare($sql);
        $done = $statement->execute($params);
    } catch (PDOException $exception) {
        throw $this->catch_and_release_pdo_exception($exception);
    }
    $count = $statement->rowCount();
    $rows = ($done && $this->is_SELECT($sql)) ? $statement->fetchAll(PDO::FETCH_ASSOC) : array();
    $statement->closeCursor(); //残りの未フェッチの行を（あれば）破棄する。
    $resultSet[] = array($sql,$count,$rows);
    return $resultSet;
  }
  private function is_SELECT($sql) {
    return ($this->is_empty($sql)) ? false : preg_match("/^(SELECT|ANALYZE|EXPLAIN|SHOW|DESC|DESCRIBE|HELP|USE)/i",$this->canonicalize_SQL($sql));
  }
  protected function canonicalize_SQL($sql) {
    return $this->shrink_spaces($this->remove_SQL_comment($sql));
  }
  private function shrink_spaces($sql) {
    return ($this->is_empty($sql)) ? $sql : preg_replace(array("/^[[:space:]]+/","/ +/","/[[:space:]]+¥z/"),array(""," ",""),$sql);
  }
  protected function remove_SQL_comment($sql) {
    return ($this->is_empty($sql)) ? $sql : preg_replace("/--[^\r\n]*[\r\n]/","\n",$sql);
  }
  private function is_empty($v) {
    return SQLotUtil::is_empty($v);
  }
  public function get_SQL_as_html_table($sql){
    $content = "";
    $count = 0;
    foreach (explode(PHP_EOL, $sql) as $line) {
      ++$count;
      $td1 = $this->tag("td",array(),strval($count));
      $td2 = $this->tag("td",array(),$this->sanitize($line));
      $content .= $this->tag("tr",array(),$td1.$td2);
    }
    $th1 = $this->tag("th",array(),$this->text("related_sql_line_number"));
    $th2 = $this->tag("th",array(),$this->text("related_sql_statement"));
    $tr = $this->tag("tr",array(),$th1.$th2);
    $thead = $this->tag("thead",array(),$tr);
    $tbody = $this->tag("tbody",array(),$content);
    $table = $this->tag("table",array("class" => "sql"),$thead.$tbody);
    return $table;
  }
  protected function tag($tagName,$attrs,$content){
    return "<".$tagName." ".$this->attrs($attrs).">".$content."</".$tagName.">";
  }
  private function attrs($attrs){
    $s = "";
    foreach ($attrs as $name => $value) {
      $s .= ("" == $s ? "" : " ").$name.'="'.$value.'"';
    }
    return $s;
  }
  protected function sanitize($text) {
    return SQLotUtil::sanitize($text);
  }
}
class SQLotUtil {
  private $textBundle = null;
  public function __construct() {
  }
  public static function is_empty($v) {
    return ((null === $v) || ("" === $v));
  }
  public static function sanitize($text) {
    return htmlspecialchars($text,ENT_QUOTES, 'UTF-8');
  }
}
class SQLotTextBundle {
  private $language;
  public function __construct($language) {
    $this->language = $language;
  }
  public function text($key) {
    return ($this->language == "ja") ? $this->text_ja($key) : $this->text_en($key) ;
  }
  protected function text_ja($key) {
    $text = array(
      "code_name" => SQLotSpecs::$code_name,
      "version" => SQLotSpecs::$version,
      "copyright" => SQLotSpecs::$copyright,
      "licenced_by" => SQLotSpecs::$licenced_by,
      "valid_upload_file_extension" => SQLotSpecs::$valid_upload_file_extension,
      "short_description" => "SQL操作ツール",
      "error_dsn_missing" => "接続でDSNを設定してください。（例: ".'"'."sqlite:test.db".'"'." や ".'"'."mysql:host=localhost;dbname=testdb".'"'."）",
      "error_sql_missing" => "SQL文を入力してください。（例: SELECT * FROM sqlite_master; SELECT name FROM sqlite_master;）",
      "error_file_too_large" => "アップロードしたファイルが大きすぎるようです。小さいファイルを試してください。",
      "error_file_upload_failed" => "アップロードに失敗しました。",
      "error_file_not_text" => "アップロードしたファイルがテキストファイルでないので処理できません。ファイルを確認してください。",
      "error_file_extension_invalid" => "アップロードしたファイルのファイル名拡張子が正しくありません。正しい拡張子".SQLotSpecs::$valid_upload_file_extension."のファイルを指定してください。",
      "UPLOAD_ERR_INI_SIZE" => "アップロードされたファイルは、php.ini の upload_max_filesize ディレクティブの値を超えています。(1)",
      "UPLOAD_ERR_FORM_SIZE" => "アップロードされたファイルは、HTML フォームで指定された MAX_FILE_SIZE を超えています。(2)",
      "UPLOAD_ERR_PARTIAL" => "アップロードされたファイルは一部のみしかアップロードされていません。(3)",
      "UPLOAD_ERR_NO_FILE" => "ファイルはアップロードされませんでした。(4)",
      "UPLOAD_ERR_NO_TMP_DIR" => "テンポラリフォルダがありません。(6)",
      "UPLOAD_ERR_CANT_WRITE" => "ディスクへの書き込みに失敗しました。(7)",
      "UPLOAD_ERR_EXTENSION" => "PHP の拡張モジュールがファイルのアップロードを中止しました。 どの拡張モジュールがファイルアップロードを中止させたのかを突き止めることはできません。 読み込まれている拡張モジュールの一覧を phpinfo() で取得すれば参考になるでしょう。(8)",
      "pdo_exception_prefix" => "データベースエラー:",
      "button_label_connection" => "接続",
      "button_label_file" => "ファイル",
      "button_label_language" => "言語",
      "button_label_help" => SQLotSpecs::$code_name." について",
      "button_label_run" => "実行",
      "button_label_pretty" => "整形",
      "area_label_connection" => "接続",
      "area_label_file" => "ファイルを指定してからSQLの実行ボタンをクリックするとファイル内のSQL文を読み込みます。",
      "area_label_language" => "言語を選択したら言語ボタンをクリックしなおしてください。",
      "area_label_sql" => "SQL",
      "input_label_dsn" => "DSN：",
      "input_label_db_user" => "DBユーザ：",
      "input_label_db_password" => "DBパスワード：",
      "option_label_en" => "英語(English)",
      "option_label_ja" => "日本語",
      "output_label_count_executed" => "実行行数:",
      "output_label_count_rows" => "取得行数:",
      "output_label_an_sql" => "SQL:",
      "th_label_row_number" => "行",
      "td_value_null" => "(空値)",
      "related_sql_line_number" => "行番号",
      "related_sql_statement" => "SQL文",
    );
    return isset($text[$key]) ? $text[$key] : "";
  }
  protected function text_en($key) {
    $text = array(
      "code_name" => SQLotSpecs::$code_name,
      "version" => SQLotSpecs::$version,
      "copyright" => SQLotSpecs::$copyright,
      "licenced_by" => SQLotSpecs::$licenced_by,
      "valid_upload_file_extension" => SQLotSpecs::$valid_upload_file_extension,
      "short_description" => "an SQL operation tool",
      "error_dsn_missing" => "Please set the DNS using Connect button. (e.g. ".'"'."sqlite:test.db".'"'.", or ".'"'."mysql:host=localhost;dbname=testdb".'"'." )",
      "error_sql_missing" => "Please input SQL statements (e.g. SELECT * FROM sqlite_master; SELECT name FROM sqlite_master;）",
      "error_file_too_large" => "Uploaded file may be too large. Try smaller file, please.",
      "error_file_upload_failed" => "Failed to upload",
      "error_file_not_text" => "Cannot accept the uploaded file because it is not a text file. Check your file, please.",
      "error_file_extension_invalid" => "Uploaded file extension is not correct. Specify a file which has a correct extension ".SQLotSpecs::$valid_upload_file_extension.", please.",
      "UPLOAD_ERR_INI_SIZE" => "The uploaded file exceeds the upload_max_filesize directive in php.ini.(1)",
      "UPLOAD_ERR_FORM_SIZE" => "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.(2)",
      "UPLOAD_ERR_PARTIAL" => "The uploaded file was only partially uploaded.(3)",
      "UPLOAD_ERR_NO_FILE" => "No file was uploaded.(4)",
      "UPLOAD_ERR_NO_TMP_DIR" => "Missing a temporary folder.(6)",
      "UPLOAD_ERR_CANT_WRITE" => "Failed to write file to disk.(7)",
      "UPLOAD_ERR_EXTENSION" => "A PHP extension stopped the file upload. PHP does not provide a way to ascertain which extension caused the file upload to stop; examining the list of loaded extensions with phpinfo() may help.(8)",
      "pdo_exception_prefix" => "Database Error:",
      "button_label_connection" => "Connect",
      "button_label_file" => "File",
      "button_label_language" => "Language",
      "button_label_help" => "About ".SQLotSpecs::$code_name,
      "button_label_run" => "Run",
      "button_label_pretty" => "Pretty",
      "area_label_connection" => "Connection",
      "area_label_file" => "Specify a file and click Run button of SQL, then the SQL in your file will be read.",
      "area_label_language" => "Choose a language and click Language button again",
      "area_label_sql" => "SQL",
      "input_label_dsn" => "DSN: ",
      "input_label_db_user" => "DB user: ",
      "input_label_db_password" => "DB password: ",
      "option_label_en" => "English",
      "option_label_ja" => "Japanese",
      "output_label_count_executed" => "Count of Executed Rows:",
      "output_label_count_rows" => "Count of Retrieved Rows:",
      "output_label_an_sql" => "SQL:",
      "th_label_row_number" => "Row",
      "td_value_null" => "(NULL)",
      "related_sql_line_number" => "Line",
      "related_sql_statement" => "SQL statement",
    );
    return isset($text[$key]) ? $text[$key] : "";
  }
}
class SQLotHTMLView {
  private $textBundle;
  public function __construct($textBundle) {
    $this->textBundle = $textBundle;
  }
  public function text($key) {
    return $this->textBundle->text($key);
  }
  public function show_new_line($sql){
    return preg_replace("/".PHP_EOL."/","<span class=".'"'."new_line".'"'.">&nbsp;</span>",$sql);
  }
  public static function spaces_to_nbsp($text) {
    return preg_replace("/ /","&nbsp;",$text);
  }
  protected function sanitize($text) {
    return SQLotUtil::sanitize($text);
  }
  protected function checked($option_value,$checked) {
    return ($option_value == $checked) ? " checked" : "";
  }
  public function write_html($request,$error,$resultSet){
?>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8">
    <title><?php echo SQLotSpecs::$code_name ?> - <?php echo $this->text("short_description") ?></title>
    <style type="text/css">
    body {background-color:#ccc;}
    textarea#sql {font-size:12pt;}
    table.sql {background-color:red; width:100%;}
    table.sql th {background-color:#ccc; color:red;}
    table.sql td {background-color:#ccc;color:red;}
    table {background-color:#666; width:100%;}
    table th {background-color:white; color:blue;}
    table td {background-color:white;}
    table th.row_number {background-color:#eee; color:black;}
    table td.row_number {background-color:#eee;text-align:center;}
    span.count_executed {color:green;font-family:serif;}
    span.count_rows {color:red;font-family:serif;}
    span.an_sql {color:blue;font-style:italic;font-family:serif;}
    span.null_value {font-style:italic;}
    span.new_line {font-size:0.5em;font-style:normal;background-color:black;color:white;}
    </style>
    <script type="text/javascript">
    function help() {
      alert("<?php echo SQLotSpecs::$code_name ?> version <?php echo $this->text('version') ?> Licenced by <?php echo $this->text('licenced_by') ?>
      " +"<?php echo $this->text('copyright') ?>");
      //See http://sourceforge.jp/projects/opensource/wiki/licenses%2FApache_License_2.0
    }
    function choose_file() {
      var display = toggle_element_by_id("file_set");
      if ("none" == display) {//ファイルを指定した場合は内容を読み込むためにsubmitする。
        document.forms.sqlForm.submit();
      }
    }
    function choose_language() {
      var display = toggle_element_by_id("language_set");
      if ("none" == display) {//切り替えて見えなくした場合は設定を有効にするためにsubmitする。
        document.forms.sqlForm.submit();
      }
    }
    function toggle_connection_set() {
      toggle_element_by_id("connection_set");
    }
    function toggle_element_by_id(id) {
      var element = document.getElementById(id);
      element.style.display = (element.style.display == "") ? "none" : "";
      return element.style.display;
    }
    function run_sql() {
      document.forms.sqlForm.submit();
    }
    function pretty_sql() {
      //SQL文を整形再表示するためにsubmitする。
      document.forms.sqlForm["need-pretty"].value = "true";
      document.forms.sqlForm.submit();
    }
    </script>
  </head>
  <body>
    <div class="header" style="background-color:#bbf;">
      <span class="title"><span class="logo"  style="padding:5;color:red;font-style:italic;font-size:25pt;"><?php echo $this->text("code_name") ?></span></span>
      <hr style="border-color:red;">
      <button id="connection" onclick="javascript:toggle_connection_set();"><?php echo $this->text("button_label_connection") ?></button>
      <button id="file" onclick="javascript:choose_file();"><?php echo $this->text("button_label_file") ?></button>
      <button id="language" onclick="javascript:choose_language();"><?php echo $this->text("button_label_language") ?></button>
      <button id="help" onclick="javascript:help();"><?php echo $this->text("button_label_help") ?></button>
    </div>
    <form name="sqlForm" method="POST" enctype="multipart/form-data">
      <fieldset id="connection_set" style="display:none;text-align:center;">
        <legend class="area_label" style="text-align:left;">
          <span id="input-connection"><?php echo $this->text("area_label_connection") ?><span>
        </legend>
        <label for="dsn"><?php echo $this->text("input_label_dsn") ?><span>
        <input type="text" name="dsn" size="60" value="<?php echo $this->sanitize(isset($request['dsn']) ? $request['dsn'] : '') ?>">
        <label for="db_user"><?php echo $this->text("input_label_db_user") ?><span>
        <input type="text" name="db_user" size="20" value="<?php echo $this->sanitize(isset($request['db_user']) ? $request['db_user'] : '') ?>">
        <label for="db_password"><?php echo $this->text("input_label_db_password") ?><span>
        <input type="password" name="db_password" size="20" value="<?php echo $this->sanitize(isset($request['db_password']) ? $request['db_password'] : '') ?>">
      </fieldset>
      <fieldset id="file_set" style="display:none;text-align:center;">
        <legend class="area_label" style="text-align:left;">
          <span id="choose-file"><?php echo $this->text("area_label_file") ?><span>
        </legend>
        <?php echo '<input type="file" name="upload">'; ?>
      </fieldset>
      <fieldset id="language_set" style="display:none;text-align:center;">
        <legend class="area_label" style="text-align:left;">
          <span id="choose-language"><?php echo $this->text("area_label_language") ?><span>
        </legend>
        <?php echo '<input type="radio" name="language" value="en" '.$this->checked("en",$this->sanitize($request['language'])).'>'.$this->text("option_label_en").'</input>'; ?>
        <?php echo '<input type="radio" name="language" value="ja" '.$this->checked("ja",$this->sanitize($request['language'])).'>'.$this->text("option_label_ja").'</input>'; ?>
      </fieldset>
      <fieldset id="sql_set">
        <legend class="area_label" style="text-align:left;">
          <label for="sql"><?php echo $this->text("area_label_sql") ?><br>
        </legend>
        <div style="text-align:left;">
          <textarea id="sql" name="sql" rows="10" cols="80"><?php echo $this->sanitize(isset($request['sql']) ? $request['sql'] : '') ?></textarea><br>
          <button id="run" onclick="javascript:run_sql();"><?php echo $this->text("button_label_run") ?></button>
          <input type="hidden" name="need-pretty" value="false">
          <button id="pretty" onclick="javascript:pretty_sql();"><?php echo $this->text("button_label_pretty") ?></button>
        </div>
        <?php echo ($error ? '<p><span id="error" style="color:red">'.print_r($error,true).'</span></p>' : '') ?>
        <div id="result_frame" style="text-align:center;width:100%;">
        <div id="result" style="text-align:left;">
        <?php
        if (!$error) {
          foreach ($resultSet as $result) {
            list($sql,$count,$rows) = $result;
            echo '[<label for="count_executed">'.$this->text("output_label_count_executed").'</label><span class="count_executed">'.$count.'</span>]';
            echo '[<label for="count_rows">'.$this->text("output_label_count_rows").'</label><span class="count_rows">'.count($rows).'</span>]';
            echo '<label for="an_sql">'.$this->text("output_label_an_sql").'</label><span class="an_sql">'.$this->show_new_line($this->spaces_to_nbsp($this->sanitize($sql))).'</span><br>';
            if (0 < count($rows)) {
              echo '<table class="result">'.PHP_EOL;
              $n = 0;
              foreach ($rows as $row) {
                if (0 == $n) {
                  echo '<thead>'.PHP_EOL;
                  echo '<tr>'.PHP_EOL;
                  echo '<th class="row_number">'.PHP_EOL;
                  echo print_r($this->text("th_label_row_number"),true);
                  echo '</th>'.PHP_EOL;
                  foreach (array_keys($row) as $column_name) {
                    echo '<th>'.PHP_EOL;
                    echo print_r($this->spaces_to_nbsp($this->sanitize($column_name)),true);
                    echo '</th>'.PHP_EOL;
                  }
                  echo '</tr>'.PHP_EOL;
                  echo '</thead>'.PHP_EOL;
                  echo '<tbody>'.PHP_EOL;
                }
                ++$n;
                echo '<tr>'.PHP_EOL;
                echo '<td class="row_number">'.PHP_EOL;
                echo print_r($n,true);
                echo '</td>'.PHP_EOL;
                foreach ($row as $cell) {
                  echo '<td>'.PHP_EOL;
                  echo ((null === $cell) ? '<span class="null_value">'.$this->text("td_value_null").'</span>' : $this->spaces_to_nbsp($this->sanitize($cell)));
                  echo '</td>'.PHP_EOL;
                }
                echo '</tr>'.PHP_EOL;
              }
              echo '</tbody>'.PHP_EOL;
              echo '</table>'.PHP_EOL;
            }
          }
        }
        ?>
        </div>
        </div>
      </fieldset>
    </form>
    <div class="footer">
    </div>
  </body>
</html>
<?php
  }
}
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {//このファイルが直接（このファイルのファイル名で）実行されたときだけ実行する。
  $o = new SQLot();
  $o->show_page($_POST);
}
?>
