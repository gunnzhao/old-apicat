<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 文档搜索类
 */
class Doc_search extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('projects_model');
        $this->load->model('doc_model');
        $this->load->model('category_model');
        $this->load->model('user_model');
    }

    public function index()
    {
        $pro_key = $this->input->get('pro_key');
        if (!$pro_key) {
            show_404();
        }

        $keyword = $this->input->get('keyword');
        if (!$keyword) {
            show_404();
        }

        $project_info = $this->projects_model->get_project_by_key($pro_key);
        if (!$project_info) {
            show_404();
        }

        $search_title_list = $this->doc_model->search_by_title($project_info['id'], $keyword);
        $search_url_list = $this->doc_model->search_by_url($project_info['id'], trim($keyword, '/'));
        $search_list = array_merge($search_title_list, $search_url_list);
        if (!$search_list) {
            return $this->render('doc_search/index', array('pro_key' => $pro_key, 'keyword' => $keyword, 'total' => 0, 'result' => array()));
        }

        // 按照update_time排序
        $update_time_arr = array_column($search_list, 'update_time');
        array_multisort($update_time_arr, SORT_DESC, $search_list);

        // 文档去重
        $ids = array();
        $result = array();
        foreach ($search_list as $v) {
            if (!in_array($v['id'], $ids)) {
                $result[] = $v;
            }
        }
        
        $cids = array_column($result, 'cid');
        $cids = array_unique($cids);
        $categories = $this->category_model->get_titles_by_ids($cids);
        $cate_arr = array();
        foreach ($categories as $v) {
            $cate_arr[$v['id']] = $v['title'];
        }

        $uids = array_column($result, 'update_uid');
        $uids = array_unique($uids);
        $users = $this->user_model->get_users_by_uids($uids);
        $user_arr = array();
        foreach ($users as $v) {
            $user_arr[$v['id']] = $v['nickname'];
        }

        return $this->render(
            'doc_search/index',
            array(
                'pro_key'    => $pro_key,
                'keyword'    => $keyword,
                'total'      => count($result),
                'result'     => $result,
                'categories' => $cate_arr,
                'users'      => $user_arr
            )
        );
    }
}