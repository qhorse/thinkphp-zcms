<?php
// +----------------------------------------------------------------------
// | Author: Jroy 
// +----------------------------------------------------------------------
namespace Home\Controller;
class ArticleController extends HomeController {
	public function index(){
		$category = $this->category();
		$this->assign('category', $category);
		$this->display($category['template_index']);
	}

	/* 文档模型列表页 */
	public function lists($p = 1){
		/* 分类信息 */
		$category = $this->category();
		//分页配置
		$p = I('get.p')?I('get.p'):1;

		/* 获取当前分类列表 */
		$Document = D('Document');
		$list = $Document->page($p,$category['list_row'])->lists($category['id']);
		$getlist = $Document->page($p,$category['list_row'])->getList($category['id']);
		$getpro = $Document->page($p,$category['list_row'])->getPro($category['id']);
		$map1['status'] = 1;
		$map1['category_id'] = $category['id'];
		$count = M('Document')->where($map1)->count();

		//当下栏目是一级栏目的，获取所有子级信息
		if(!strcmp($category['pid'], '0') !== 0 && empty($list)){
			$map['pid'] = $category['id'];
			$child_ids = M('category')->field('id')->where($map)->select();
			$child = implode(',',array_column($child_ids,'id'));
			$map2['category_id'] = array('in',$child);
			$map2['status'] = 1;
			$list = $Document->page($p, $category['list_row'])->where($map2)->lists();
			$count = M('Document')->where($map2)->count();
		}

		/*分页实现*/
		$Page = new \Think\Page($count,$category['list_row']);
		$Page->setConfig('next','下一页');
		$Page->setConfig('prev','上一页');
		$show = $Page->show();
		$this->assign('_page',$show);

		/* 获取模板 */
		if(!empty($category['template_lists'])){ //分类已定制模板
			$tmpl = $category['template_lists'];
		} elseif($category['model']>2) { //使用默认模板
			$tmpl = 'Article/list_'. get_model($category['model'],'name');
		}else{
			$tmpl = 'Article/list';
		}
		/* 模板赋值并渲染模板 */
		$this->assign('category', $category);
		$this->assign('list', $list);
		$this->assign('getlist',$getlist);
		$this->assign('getpro',$getpro);
		$this->display($tmpl);
	}

	/* 文档模型详情页 */
	public function show($id = 0, $p = 1){
		/* 标识正确性检测 */
		if(!($id && is_numeric($id))){
			$this->error('文档ID错误！');
		}

		/* 页码检测 */
		$p = intval($p);
		$p = empty($p) ? 1 : $p;

		/* 获取详细信息 */
		$Document = D('Document');
		$info = $Document->show($id);
		if(!$info){
			$this->error($Document->getError());
		}

		/* 分类信息 */
		$category = $this->category($info['category_id']);

		/* 获取模板 */
		if(!empty($info['template'])){//已定制模板
			$tmpl = $info['template'];
		} elseif (!empty($category['template_detail'])){ //分类已定制模板
			$tmpl = $category['template_detail'];
		} elseif(strcmp($category['model'], '2') !== 0) { //使用默认模板
			$tmpl = 'Article/show_'. get_model($category['model'],'name');
		}else{
			$tmpl = 'Article/show';
		}
		/* 更新浏览数 */
		$map = array('id' => $id);
		$Document->where($map)->setInc('view');

		/* 模板赋值并渲染模板 */
		$this->assign('category', $category);
		$this->assign('info', $info);
		$this->assign('page', $p); //页码
		$this->display($tmpl);
	}

	/* 文档分类检测 */
	private function category($cid = 0){
		/* 标识正确性检测 */
		$id = $id ? $id : I('get.cid', 0);

		if(empty($id)){
			$this->error('没有指定文档分类！');
		}

		/* 获取分类信息 */
		$category = D('Category')->info($id);
		if($category && 1 == $category['status']){
			switch ($category['display']) {
				case 0:
					$this->error('该分类禁止显示！');
					break;
				//TODO: 更多分类显示状态判断
				default:
					return $category;
			}
		} else {
			$this->error('分类不存在或被禁用！');
		}
	}

	public function page($cid)
	{
		$category = $this->category($cid);
		if(!empty($category['template_index'])){
			$tpl = $category['template_index'];
		}elseif(!empty($category['template_list'])){
			$tpl = $category['template_list'];
		}elseif(!empty($category['template_detail'])){
			$tpl = $category['template_detail'];
		}else{
			$tpl = 'Article/page';
		}
		$content =M('category_content')->where("id=$cid")->find();
		$this->assign('category',$category);
		$this->assign('content',$content);
		$this->display($tpl);
	}
}
