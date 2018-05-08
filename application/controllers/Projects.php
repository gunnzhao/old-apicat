<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 用户项目类
 */
class Projects extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('projects_model');
    }

    public function index()
    {
        $records = $this->projects_model->project_records($this->session->uid);

        $uids = array();
        foreach ($records as $v) {
            if (!in_array($v['update_uid'], $uids)) {
                $uids[] = $v['update_uid'];
            }
        }

        // 去查询修改项目的用户昵称
        $this->load->model('user_model');
        $user_arr = $this->user_model->get_users_by_uids($uids);
        $users = array();
        foreach ($user_arr as $v) {
            $users[$v['id']] = $v['nickname'];
        }

        $result = array();
        foreach ($records as $v) {
            $result[] = array(
                'id'          => $v['id'],
                'pro_key'     => $v['pro_key'],
                'title'       => $v['title'],
                'update_time' => $v['update_time'],
                'update_user' => $users[$v['update_uid']]
            );
        }

        $this->add_page_css('/static/css/projects.css');
        $this->add_page_js('/static/js/projects.js');
        $this->render('projects/index', array('records' => $result));
    }

    public function do_add()
    {
        $title = trim($this->input->post('title'));
        if (empty($title)) {
            return $this->response_json_fail('请输入项目名称');
        }

        $authority = (int)$this->input->post('authority');
        if ($authority !== 0 and $authority !== 1) {
            return $this->response_json_fail('请选择项目权限');
        }

        $description = trim($this->input->post('description'));
        
        $res = $this->projects_model->add_project($this->session->uid, $title, $authority, $description);
        if (!$res) {
            return $this->response_json_fail('项目创建失败，请重试。');
        }
        $this->response_json_ok();
    }

    public function settings()
    {
        $pid = $this->input->get('pid');
        if (!$pid) {
            show_404();
        }

        $project_info = $this->projects_model->get_project_by_id($pid);
        if (!$project_info) {
            show_404();
        }

        if ($project_info['uid'] != $this->session->uid) {
            show_404();
        }

        $this->load->helper('form_msg');
        $this->render('projects/settings', array('project_info' => $project_info));
    }

    public function do_settings()
    {
        $this->load->helper('form_msg');
        init_form_post(array('pid', 'title', 'authority', 'description'));
        $pid = trim($this->input->post('pid'));
        if (!$pid) {
            $this->show_err('修改失败');
        }

        $title = trim($this->input->post('title'));
        if (!$title) {
            $this->show_err('请输入项目名称');
        }

        $authority = trim($this->input->post('authority'));
        if ($authority != 0 and $authority != 1) {
            $this->show_err('修改失败');
        }

        $description = trim($this->input->post('description'));
        $owner = $this->projects_model->check_owner($this->session->uid, $pid);
        if (!$owner) {
            $this->show_err('修改失败');
        }

        $is_repeat = $this->projects_model->check_title_repeat($this->session->uid, $pid, $title);
        if ($is_repeat) {
            $this->show_err('项目名称重复');
        }

        $res = $this->projects_model->edit_project_by_id(
            array('title' => $title, 'authority' => $authority, 'description' => $description, 'update_uid' => $this->session->uid),
            $pid
        );
        if ($res !== false) {
            $this->show_ok('修改成功');
        } else {
            $this->show_err('修改失败，请稍后重试');
        }
    }

    public function members()
    {
        $this->add_page_css('/static/css/projects.members.css');
        $this->render('projects/members');
    }
}