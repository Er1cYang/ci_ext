CodeIgniter Extensions
======

######安装  
解压后，将src复制到项目的任意地方，比如/system/ci_ext  

######测试  
`
// controllers/test.php

// 首先引入必要文件CodeIgniter_Extension
include BASEPATH.'/ci_ext/CodeIgniter_Extension.php';
// 然后调用静态方法setup即可
CodeIgniter_Extension::setup();

use \ci_ext\db\Table;

class Test extends CI_Controller {
	public function index() {
		// 使用Table查询数据
		$model = new Classes();
		// 查询一条
		$record = $model->find();
		// 输出结果
		var_dump($record->attributes);
	}
}

// 这是一个model
class Classes extends Table {
	
	public function tableName() {
		return 'class';
	}
	
}
`