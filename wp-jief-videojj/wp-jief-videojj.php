<?php  
/*
Plugin Name: Video++ Player
Plugin URI: http://www.jieerf.com
Description: Video++是全球首创的视频内生态系统（ Video OS），只要你能看到视频的地方，都能使用Video++的技术。使用方法：编辑文章添加[videojj]视频地址[/videojj],如[videojj]http://v.youku.com/v_show/id_XNzIxODU2NTQw.html[/videojj]，如果解析失败请联系插件作者更新。
Version: 1.0.0
Author: 杰尔夫技术
Author URI: http://www.jieerf.com/
License: GPL
*/

define('JIEFVIDEOJJ_VERSION', '1.0.0');
define('JIEFVIDEOJJ_URL', plugins_url('', __FILE__));
define('JIEFVIDEOJJ_PATH', dirname( __FILE__ ));

$jiefVideo = new JiefVideo();

class JiefVideo{
    // 构造方法
    public function __construct(){
        if(is_admin()){
            // 后台管理员设置项
            add_action('admin_menu', array($this, 'admin_menu'));
        }

        /* videojj 短代码 */
        // 添加一个videojj的段代码
        add_shortcode('videojj', array($this, 'admin_iva') );
        // 添加后台快捷按钮
        add_action('admin_print_footer_scripts', array($this, 'add_videojj_quicktags') );

        /* upinfo 短代码 */
        add_shortcode('upinfo', array($this, 'admin_upinfo') );
        add_action('admin_print_footer_scripts', array($this, 'add_upinfo_quicktags') );
    }

    // 增加后台插件设置
    public function admin_menu(){
        add_plugins_page('VideoJJ 设置', 'VideoJJ 设置', 'manage_options', 'jief_videojj_settings', array($this, 'admin_settings'));
    }

    // 设置界面
    public function admin_settings(){
        if($_POST['jief_videojj_submit'] == '保存'){
            $param = array('appkey');
            $json = array();
            foreach($_POST as $key => $val){
                if(in_array($key, $param)){
                    $json[$key] = $val;
                }
            }
            $json = json_encode($json); 
            update_option('jief_videojj_option', $json);
        }
        $option = $this->_get_option_json();
        echo '<h2>VideoJJ Player 设置</h2>';
        echo '<form action="" method="post">
            <table class="form-table">
            <tr valign="top">
                <th scope="row">APPKey</th>
                <td>
                    <label><input type="text" class="regular-text code" name="appkey" value="'.$option['appkey'].'"></label>
                    <br />
                    <p class="description">申请地址：http://www.videojj.com</p>
                </td>
            </tr>
            </table>
            <p class="submit"><input type="submit" name="jief_videojj_submit" id="submit" class="button-primary" value="保存"></p>
            </form>
            <script type="text/javascript">
            function jiefVideojjGetBilibili() {
                var url = document.getElementById("_jief_bilibili_url").value;
                if( url == "" ) {
                    alert( "视频地址不能为空" ); return;
                }
                document.getElementById("_jief_bilibili_html5url").innerHTML = "获取中...";
                var jsonp = document.createElement("script");  
                jsonp.type = "text/javascript";
                jsonp.src = "http://www.jieerf.com/bilibili/api.php?url="+url+"&error=jiefVideojjError&success=jiefVideojjSuccess";
                document.getElementsByTagName("head")[0].appendChild(jsonp);
            }
            function jiefVideojjError(msg) {
                alert(msg);
            }
            function jiefVideojjSuccess(html5url) {
                document.getElementById("_jief_bilibili_html5url").innerHTML = html5url;
            }
            </script>
            <h2>哔哩哔哩视频HTML5播放器获取</h2>
            <table class="form-table">
                <tr valign="top">
                <td>
                    <input type="text" class="regular-text code" style="width:50%;" id="_jief_bilibili_url" placeholder="哔哩哔哩视频地址，如：http://www.bilibili.com/video/av6763249/" >
                    <a href="javascript:jiefVideojjGetBilibili();" class="button" type="button">查询</a>
                </td>
                </tr>
                <tr valign="top">
                <td>
                    <p id="_jief_bilibili_html5url"></p>
                </td>
                </tr>
            </table>
        ';
        echo '<h2>意见反馈</h2>
            <p>你的意见是VideoJJ Player成长的原动力，<a href="http://www.jieerf.com/" target="_blank">欢迎给我们留言</a>，或许你想要的功能下一个版本就会实现哦！</p>
        ';
    }

    // 获得插件数据库存储
    private function _get_option_json() {
        $option = get_option('jief_videojj_option');
        if(!empty($option)){
            $option = json_decode($option, true);
        }
        return $option;
    }

    // 获得iframe控件
    private function _get_iframe( $url = '', $height = '', $atts ) {
        $style .= "width: 100%; padding-bottom:10px;";
        if(!empty($height)){
            $style .= "height: {$height}px;";
        }
        if(!empty($style)){
            $style = ' style="' . $style . '"';
        }
        $html .= '<div id="_jief_videojj_iframe" class="jief-videojj-iframe"' . $style . '>
        <iframe src="' . $url . '" width="100%" height="100%" frameborder="0" allowfullscreen="true"></iframe>
        </div>';
        return $html;
    }

    // 获得Video++播放器
    private function _get_videojj( $url = '', $atts ) {
        //extract(shortcode_atts(array("title"=>''),$atts));
        $option = $this->_get_option_json();
        $appkey = $option['appkey'];
        $height = $this->_is_mobile() ? 300 : 540 ;

        /* 新版，官方推荐使用，待测试稳定性 */
        $video_html = sprintf('<link rel="stylesheet" href="%1$s" type="text/css" media="screen">', JIEFVIDEOJJ_URL . '/static/style.css?ver=' . JIEFVIDEOJJ_VERSION);
        $video_html .= '<div class="jief-videojj-warning">如无法播放，请重新刷新页面哦！</div>';
        // $video_html .= '<div id="_jief_videojj_player" class="jief-videojj-player" style="height:'.$height.'px;"></div>
        // <script type="text/javascript" src="http://cytron.cdn.videojj.com/latest/cytron.core.js"></script>
        // <script type="text/javascript">
        // var ivaInstance = new Iva( "_jief_videojj_player", {
        //     appkey: "'.$appkey.'",
        //     video: "'.$url.'",
        //     editorEnable: false,
        //     vorEnable: false,
        //     vorStartGuideEnable: false
        // });
        // </script>';
        /* 旧版 */
        $video_html .= '<div id="_jief_videojj_player" class="jief-videojj-player" style="height:'.$height.'px;"></div>
        <script type="text/javascript" src="http://7xjfim.com2.z0.glb.qiniucdn.com/Iva.js"></script>
        <script type="text/javascript">
        var ivaInstance = new Iva( "_jief_videojj_player", {
            appkey: "'.$appkey.'",
            video: "'.$url.'",
            title: "",
            cover: ""
        });
        </script>';
        return $video_html;
    }
    
    // 短代码的处理方法
    public function admin_iva( $atts, $content='' ) {

        if( strpos( $content, 'www.bilibili.com' ) !== false ) {
            return $this->_get_iframe( $content, '540' );
        } else {
            return $this->_get_videojj( $content );
        }
        
    }

    // upinfo短代码的处理方法
    public function admin_upinfo( $atts, $content='' ) {
        extract(shortcode_atts(array( "name"=>'', "face"=>'' ),$atts));
        $upinfo_html = sprintf('<link rel="stylesheet" href="%1$s" type="text/css" media="screen">', JIEFVIDEOJJ_URL . '/static/style.css?ver=' . JIEFVIDEOJJ_VERSION);
        $upinfo_html .= '<div class="jief-upinfo">
        <div class="jief-u-face">
        <img src="'.$face.'" alt="'.$name.'">
        </div>
        <div class="jief-r-info">
            <div class="jief-r-usname">'.$name.'</div>
            <div class="jief-r-sign">'.$content.'</div>
        </div>
        </div>';
        return $upinfo_html;
    }
    
    // 是否是手机端
    private function _is_mobile() { 
        // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
        if (isset ($_SERVER['HTTP_X_WAP_PROFILE'])) {
            return true;
        } 
        // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
        if (isset ($_SERVER['HTTP_VIA'])) { 
            // 找不到为flase,否则为true
            return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
        } 
        // 脑残法，判断手机发送的客户端标志,兼容性有待提高
        if (isset ($_SERVER['HTTP_USER_AGENT'])) {
            $clientkeywords = array ('iphone',
                'ipad',
                'android',
                'ipod',
                ); 
            // 从HTTP_USER_AGENT中查找手机浏览器的关键字
            if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
                return true;
            } 
        } 
        // 协议法，因为有可能不准确，放到最后判断
        if (isset ($_SERVER['HTTP_ACCEPT'])) { 
            // 如果只支持wml并且不支持html那一定是移动设备
            // 如果支持wml和html但是wml在html之前则是移动设备
            if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
                return true;
            } 
        }
        return false;
    }

    // 增加后台快捷按钮
    public function add_videojj_quicktags() {
        echo '<script type="text/javascript">';
        echo 'QTags.addButton( "videojj", "Video++播放器", "[videojj]这里填写你的视频地址[/videojj]\n","" )'; 
        echo '</script>';
    }

    // 增加主播信息快捷按钮
    public function add_upinfo_quicktags() {
        echo '<script type="text/javascript">
        QTags.addButton( "upinfo", "UP主播信息", "[upinfo name=\"名称\" face=\"头像地址\"]这里填写主播描述信息[/upinfo]\n","" )
        </script>';
    }
}