<?php
/*
 * 青和简约单页导航 - 管理后台
 */

// 登录密码（可自行修改）
define('ADMIN_PASSWORD', 'admin123');

// 数据库配置
$servername = "localhost";
$username = "nav";
$password = "";
$dbname = "nav";

// 登录验证
session_start();
if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    if (isset($_POST['password'])) {
        if ($_POST['password'] === ADMIN_PASSWORD) {
            $_SESSION['admin'] = true;
            header('Location: admin.php');
            exit;
        } else {
            $error = '密码错误';
        }
    }
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理后台 - 登录</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f9f9f9;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        .login-box {
            background: #fff;
            padding: 3rem;
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
            text-align: center;
            width: 320px;
        }
        .login-box h2 { margin-bottom: 1.5rem; color: #333; }
        .login-box input {
            width: 100%;
            padding: 0.8rem;
            margin-bottom: 1rem;
            border: 2px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            outline: none;
        }
        .login-box input:focus { border-color: #666; }
        .login-box button {
            width: 100%;
            padding: 0.8rem;
            background-color: #333;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
        }
        .login-box button:hover { background-color: #555; }
        .error { color: #e74c3c; margin-bottom: 1rem; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>管理后台</h2>
        <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
        <form method="post">
            <input type="password" name="password" placeholder="请输入管理密码" required>
            <button type="submit">登录</button>
        </form>
    </div>
</body>
</html>
<?php
    exit;
}

// 退出登录
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['admin']);
    header('Location: admin.php');
    exit;
}

// 连接数据库
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die('数据库连接失败');
}
$conn->set_charset('utf8mb4');

// 处理分类操作
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['do'])) {
    $do = $_POST['do'];

    // 添加分类
    if ($do === 'add_cat') {
        $name = trim($_POST['name']);
        $status = isset($_POST['status']) ? 1 : 0;
        if ($name !== '') {
            $stmt = $conn->prepare("INSERT INTO category (name, status) VALUES (?, ?)");
            $stmt->bind_param('si', $name, $status);
            $stmt->execute();
            $stmt->close();
        }
        header('Location: admin.php');
        exit;
    }

    // 编辑分类
    if ($do === 'edit_cat') {
        $id = intval($_POST['id']);
        $name = trim($_POST['name']);
        $status = isset($_POST['status']) ? 1 : 0;
        $stmt = $conn->prepare("UPDATE category SET name = ?, status = ? WHERE id = ?");
        $stmt->bind_param('sii', $name, $status, $id);
        $stmt->execute();
        $stmt->close();
        header('Location: admin.php');
        exit;
    }

    // 删除分类
    if ($do === 'del_cat') {
        $id = intval($_POST['id']);
        // 检查是否有链接
        $rs = $conn->query("SELECT COUNT(*) as cnt FROM data WHERE fid = $id");
        $row = $rs->fetch_assoc();
        if ($row['cnt'] > 0) {
            $del_error = '该分类下有链接，无法删除';
        } else {
            $conn->query("DELETE FROM category WHERE id = $id");
        }
        header('Location: admin.php');
        exit;
    }

    // 添加链接
    if ($do === 'add_link') {
        $fid = intval($_POST['fid']);
        $name = trim($_POST['name']);
        $url = trim($_POST['url']);
        if ($name !== '' && $url !== '') {
            $stmt = $conn->prepare("INSERT INTO data (fid, name, url) VALUES (?, ?, ?)");
            $stmt->bind_param('iss', $fid, $name, $url);
            $stmt->execute();
            $stmt->close();
        }
        header('Location: admin.php');
        exit;
    }

    // 编辑链接
    if ($do === 'edit_link') {
        $id = intval($_POST['id']);
        $fid = intval($_POST['fid']);
        $name = trim($_POST['name']);
        $url = trim($_POST['url']);
        $stmt = $conn->prepare("UPDATE data SET fid = ?, name = ?, url = ? WHERE id = ?");
        $stmt->bind_param('issi', $fid, $name, $url, $id);
        $stmt->execute();
        $stmt->close();
        header('Location: admin.php');
        exit;
    }

    // 删除链接
    if ($do === 'del_link') {
        $id = intval($_POST['id']);
        $conn->query("DELETE FROM data WHERE id = $id");
        header('Location: admin.php');
        exit;
    }
}

// 读取分类
$categories = [];
$rs = $conn->query("SELECT * FROM category ORDER BY created_at DESC");
while ($row = $rs->fetch_assoc()) {
    $categories[] = $row;
}

// 读取链接
$links = [];
$rs = $conn->query("SELECT d.*, c.name as cat_name FROM data d LEFT JOIN category c ON d.fid = c.id ORDER BY d.time DESC");
while ($row = $rs->fetch_assoc()) {
    $links[] = $row;
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes">
    <title>管理后台</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { font-size: 16px; }
        @media (max-width: 768px) { html { font-size: 14px; } }
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f9f9f9;
            color: #333;
            padding: 1rem;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        header h1 { font-size: 1.8rem; }
        header a {
            color: #666;
            text-decoration: none;
            font-size: 0.9rem;
        }
        header a:hover { color: #333; }

        .section {
            background: #fff;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }
        .section h2 {
            font-size: 1.3rem;
            margin-bottom: 1.2rem;
            padding-bottom: 0.8rem;
            border-bottom: 2px solid #f0f0f0;
            color: #333;
        }

        /* 表格 - 桌面样式 */
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            text-align: left;
            padding: 0.8rem 0.5rem;
            border-bottom: 1px solid #f0f0f0;
        }
        th {
            color: #666;
            font-weight: normal;
            font-size: 0.85rem;
        }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background-color: #fafafa; }

        .badge {
            display: inline-block;
            padding: 0.2rem 0.5rem;
            border-radius: 3px;
            font-size: 0.75rem;
        }
        .badge-on { background: #e8f5e9; color: #2e7d32; }
        .badge-off { background: #f5f5f5; color: #999; }

        .actions { display: flex; gap: 0.5rem; }
        .actions button, .actions a {
            padding: 0.5rem 0.8rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.85rem;
            text-decoration: none;
        }
        .btn-edit { background: #f5f5f5; color: #333; }
        .btn-edit:hover { background: #e0e0e0; }
        .btn-del { background: #ffebee; color: #c62828; }
        .btn-del:hover { background: #ffcdd2; }

        .add-form {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: center;
        }
        .add-form input, .add-form select {
            padding: 0.5rem 0.7rem;
            border: 2px solid #ddd;
            border-radius: 4px;
            font-size: 0.9rem;
            outline: none;
        }
        .add-form input:focus, .add-form select:focus { border-color: #666; }
        .add-form input[type="text"] { flex: 1; min-width: 120px; }
        .add-form input[type="url"] { flex: 2; min-width: 200px; }
        .add-form button {
            padding: 0.5rem 1.2rem;
            background: #333;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        .add-form button:hover { background: #555; }

        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.4);
            align-items: center;
            justify-content: center;
            z-index: 100;
        }
        .modal.show { display: flex; }
        .modal-box {
            background: #fff;
            padding: 2rem;
            border-radius: 8px;
            width: 380px;
            max-width: calc(100vw - 2rem);
        }
        .modal-box h3 { margin-bottom: 1.2rem; font-size: 1.2rem; }
        .modal-box input, .modal-box select {
            width: 100%;
            padding: 0.7rem;
            margin-bottom: 0.8rem;
            border: 2px solid #ddd;
            border-radius: 4px;
            font-size: 0.95rem;
            outline: none;
        }
        .modal-box input:focus, .modal-box select:focus { border-color: #666; }
        .modal-box .row { display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.8rem; }
        .modal-box .row input[type="checkbox"] { width: auto; margin-bottom: 0; }
        .modal-btns { display: flex; gap: 0.5rem; margin-top: 1rem; }
        .modal-btns button { flex: 1; padding: 0.7rem; border: none; border-radius: 4px; cursor: pointer; font-size: 0.95rem; }
        .modal-btns .btn-submit { background: #333; color: #fff; }
        .modal-btns .btn-submit:hover { background: #555; }
        .modal-btns .btn-cancel { background: #f5f5f5; color: #333; }
        .modal-btns .btn-cancel:hover { background: #e0e0e0; }

        .error-msg {
            background: #ffebee;
            color: #c62828;
            padding: 0.8rem 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            font-size: 0.9rem;
        }

        /* 移动端响应式样式 */
        @media (max-width: 768px) {
            body { padding: 0.5rem; }
            .section { padding: 1rem; }
            header { flex-direction: column; align-items: flex-start; gap: 0.5rem; margin-bottom: 1.5rem; }
            
            /* 表单在移动端垂直堆叠 */
            .add-form { 
                flex-direction: column; 
                align-items: stretch; 
                gap: 0.8rem;
            }
            .add-form input, .add-form select, .add-form button { 
                width: 100%; 
                padding: 0.8rem;
                font-size: 1rem;
            }
            .add-form input[type="text"], .add-form input[type="url"] { 
                flex: none; 
                min-width: auto; 
            }
            
            /* 表格在移动端转换为卡片式布局 */
            table, thead, tbody, th, td, tr { 
                display: block; 
            }
            thead { 
                display: none; 
            }
            tr {
                margin-bottom: 1rem;
                padding: 1rem;
                border: 1px solid #eee;
                border-radius: 6px;
                background: #fff;
            }
            td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0.6rem 0;
                border-bottom: 1px dashed #f0f0f0;
                text-align: right;
            }
            td:last-child {
                border-bottom: none;
            }
            td:before {
                content: attr(data-label);
                font-weight: bold;
                color: #666;
                text-align: left;
                margin-right: 1rem;
                flex-shrink: 0;
            }
            
            /* 操作按钮在移动端占满宽度 */
            .actions { 
                justify-content: stretch; 
                gap: 0.8rem; 
            }
            .actions button { 
                flex: 1; 
                padding: 0.7rem; 
                text-align: center;
            }
            
            .modal-box {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
<div class="container">

    <header>
        <h1>管理后台</h1>
        <a href="admin.php?action=logout">退出登录</a>
    </header>

    <?php if (isset($del_error)): ?>
        <div class="error-msg"><?php echo $del_error; ?></div>
    <?php endif; ?>

    <!-- 分类管理 -->
    <div class="section">
        <h2>分类管理</h2>
        <form method="post" class="add-form" style="margin-bottom:1.5rem;">
            <input type="hidden" name="do" value="add_cat">
            <input type="text" name="name" placeholder="分类名称" required>
            <label style="font-size:0.9rem; color:#666;">
                <input type="checkbox" name="status" value="1" checked> 启用
            </label>
            <button type="submit">添加分类</button>
        </form>

        <table id="categoryTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>名称</th>
                    <th>状态</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $cat): ?>
                <tr>
                    <td><?php echo $cat['id']; ?></td>
                    <td><?php echo htmlspecialchars($cat['name']); ?></td>
                    <td>
                        <span class="badge <?php echo $cat['status'] ? 'badge-on' : 'badge-off'; ?>">
                            <?php echo $cat['status'] ? '启用' : '禁用'; ?>
                        </span>
                    </td>
                    <td>
                        <div class="actions">
                            <button class="btn-edit" onclick='openCatModal(<?php echo json_encode(["id"=>$cat["id"],"name"=>htmlspecialchars($cat["name"]),"status"=>$cat["status"]]); ?>)'>编辑</button>
                            <form method="post" style="display:inline;" onsubmit="return confirm('确定删除该分类？');">
                                <input type="hidden" name="do" value="del_cat">
                                <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
                                <button type="submit" class="btn-del">删除</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- 链接管理 -->
    <div class="section">
        <h2>链接管理</h2>
        <form method="post" class="add-form" style="margin-bottom:1.5rem;">
            <input type="hidden" name="do" value="add_link">
            <select name="fid" required>
                <option value="">选择分类</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="name" placeholder="链接名称" required>
            <input type="url" name="url" placeholder="https://" required>
            <button type="submit">添加链接</button>
        </form>

        <table id="linkTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>名称</th>
                    <th>链接</th>
                    <th>所属分类</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($links as $link): ?>
                <tr>
                    <td><?php echo $link['id']; ?></td>
                    <td><?php echo htmlspecialchars($link['name']); ?></td>
                    <td><a href="<?php echo htmlspecialchars($link['url']); ?>" target="_blank" style="color:#666; text-decoration:none;"><?php echo htmlspecialchars($link['url']); ?></a></td>
                    <td><?php echo htmlspecialchars($link['cat_name']); ?></td>
                    <td>
                        <div class="actions">
                            <button class="btn-edit" onclick='openLinkModal(<?php echo json_encode(["id"=>$link["id"],"fid"=>$link["fid"],"name"=>htmlspecialchars($link["name"]),"url"=>htmlspecialchars($link["url"])]); ?>)'>编辑</button>
                            <form method="post" style="display:inline;" onsubmit="return confirm('确定删除该链接？');">
                                <input type="hidden" name="do" value="del_link">
                                <input type="hidden" name="id" value="<?php echo $link['id']; ?>">
                                <button type="submit" class="btn-del">删除</button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>

<!-- 分类编辑弹窗 -->
<div id="catModal" class="modal">
    <div class="modal-box">
        <h3>编辑分类</h3>
        <form method="post">
            <input type="hidden" name="do" value="edit_cat">
            <input type="hidden" name="id" id="cat_id">
            <input type="text" name="name" id="cat_name" required>
            <div class="row">
                <input type="checkbox" name="status" id="cat_status" value="1">
                <label for="cat_status">启用</label>
            </div>
            <div class="modal-btns">
                <button type="submit" class="btn-submit">保存</button>
                <button type="button" class="btn-cancel" onclick="closeCatModal()">取消</button>
            </div>
        </form>
    </div>
</div>

<!-- 链接编辑弹窗 -->
<div id="linkModal" class="modal">
    <div class="modal-box">
        <h3>编辑链接</h3>
        <form method="post">
            <input type="hidden" name="do" value="edit_link">
            <input type="hidden" name="id" id="link_id">
            <select name="fid" id="link_fid" required>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="name" id="link_name" required>
            <input type="url" name="url" id="link_url" required>
            <div class="modal-btns">
                <button type="submit" class="btn-submit">保存</button>
                <button type="button" class="btn-cancel" onclick="closeLinkModal()">取消</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCatModal(data) {
    document.getElementById('cat_id').value = data.id;
    document.getElementById('cat_name').value = data.name;
    document.getElementById('cat_status').checked = data.status == 1;
    document.getElementById('catModal').classList.add('show');
}
function closeCatModal() {
    document.getElementById('catModal').classList.remove('show');
}

function openLinkModal(data) {
    document.getElementById('link_id').value = data.id;
    document.getElementById('link_fid').value = data.fid;
    document.getElementById('link_name').value = data.name;
    document.getElementById('link_url').value = data.url;
    document.getElementById('linkModal').classList.add('show');
}
function closeLinkModal() {
    document.getElementById('linkModal').classList.remove('show');
}

// 点击弹窗背景关闭
document.querySelectorAll('.modal').forEach(m => {
    m.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('show');
        }
    });
});

// 移动端表格适配：为每个 td 添加 data-label 属性
function initMobileTable() {
    if (window.innerWidth > 768) return; // 仅在移动端生效
    
    // 处理分类表格
    const categoryTable = document.getElementById('categoryTable');
    if (categoryTable) {
        const headers = Array.from(categoryTable.querySelectorAll('th')).map(th => th.textContent.trim());
        categoryTable.querySelectorAll('tbody tr').forEach(tr => {
            const cells = tr.querySelectorAll('td');
            cells.forEach((td, index) => {
                if (headers[index]) {
                    td.setAttribute('data-label', headers[index]);
                }
            });
        });
    }
    
    // 处理链接表格
    const linkTable = document.getElementById('linkTable');
    if (linkTable) {
        const headers = Array.from(linkTable.querySelectorAll('th')).map(th => th.textContent.trim());
        linkTable.querySelectorAll('tbody tr').forEach(tr => {
            const cells = tr.querySelectorAll('td');
            cells.forEach((td, index) => {
                if (headers[index]) {
                    // 对于链接列，需要特殊处理，因为包含<a>标签
                    if (index === 2) { // 链接列
                        const originalText = td.querySelector('a') ? td.querySelector('a').textContent : td.textContent;
                        td.setAttribute('data-label', headers[index] + ' (点击查看)');
                    } else {
                        td.setAttribute('data-label', headers[index]);
                    }
                }
            });
        });
    }
}

// 页面加载和窗口大小变化时执行
window.addEventListener('DOMContentLoaded', initMobileTable);
window.addEventListener('resize', initMobileTable);
</script>
</body>
</html>
