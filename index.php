<?php
/*
 * 青和简约单页导航
 * 作者：青和
 * QQ：1722791510
 * 邮箱：admin@7h.fit
 * love msq
 */

// 数据库配置
$servername = "localhost";
$username = "nav";
$password = "";
$dbname = "nav";

// 处理搜索
$searchData = [];
if (isset($_GET['q']) && trim($_GET['q']) !== '') {
    $keyword = trim($_GET['q']);
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("数据库连接失败: " . $conn->connect_error);
    }
    $sql = "SELECT name, url FROM data WHERE name LIKE ? OR url LIKE ?";
    $stmt = $conn->prepare($sql);
    $param = "%{$keyword}%";
    $stmt->bind_param("ss", $param, $param);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $searchData[] = $row;
    }
    $stmt->close();
    $conn->close();
} else {
    // 查询分类和数据
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("数据库连接失败: " . $conn->connect_error);
    }

    // 查询分类
    $categories = [];
    $catIds = [];
    $catSql = "SELECT id, name FROM category WHERE status = 1 ORDER BY created_at DESC";
    $catResult = $conn->query($catSql);
    if ($catResult->num_rows > 0) {
        while ($row = $catResult->fetch_assoc()) {
            $categories[$row['id']] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'data' => []
            ];
            $catIds[] = $row['id'];
        }
    }

    // 一次性查询所有分类的数据
    if (!empty($catIds)) {
        $dataSql = "SELECT fid, name, url FROM data WHERE fid IN (" . implode(',', $catIds) . ") ORDER BY time DESC";
        $dataResult = $conn->query($dataSql);
        if ($dataResult->num_rows > 0) {
            while ($row = $dataResult->fetch_assoc()) {
                $fid = $row['fid'];
                if (isset($categories[$fid])) {
                    $categories[$fid]['data'][] = [
                        'name' => $row['name'],
                        'url' => $row['url']
                    ];
                }
            }
        }
    }
    $categories = array_values($categories);
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>青和导航</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f9f9f9;
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .logo {
            font-size: 1.8rem;
            font-weight: bold;
            color: #333;
            text-decoration: none;
        }
        .time {
            color: #666;
            font-size: 0.9rem;
        }
        .banner {
            position: relative;
            height: 220px;
            background: url('bg.jpg') center/cover no-repeat;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            margin-bottom: 2rem;
        }
        .banner::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            /* 滤镜效果 */
            background-color: rgba(0, 0, 0, 0.5);
        }
        .banner h1 {
            position: relative;
            z-index: 1;
            font-size: 2.5rem;
            letter-spacing: 2px;
        }
        .search {
            max-width: 600px;
            margin: 0 auto 3rem;
            padding: 0 1rem;
        }
        .search form {
            display: flex;
        }
        .search input {
            flex: 1;
            padding: 0.9rem 1rem;
            border: 2px solid #ddd;
            border-right: none;
            border-radius: 4px 0 0 4px;
            font-size: 1rem;
            outline: none;
            transition: border 0.3s;
        }
        .search input:focus {
            border-color: #666;
        }
        .search button {
            padding: 0 1.5rem;
            background-color: #333;
            color: white;
            border: none;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
            transition: background 0.3s;
        }
        .search button:hover {
            background-color: #555;
        }
        .categories {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem 3rem;
        }
        .category {
            background-color: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }
        .category h2 {
            color: #333;
            margin-bottom: 1.2rem;
            padding-bottom: 0.8rem;
            border-bottom: 2px solid #f0f0f0;
            font-size: 1.5rem;
        }
        .links {
            display: flex;
            flex-wrap: wrap;
            gap: 0.8rem;
            justify-content: center;
        }
        .links a {
            display: inline-block;
            padding: 0.6rem 1.2rem;
            background-color: #f5f5f5;
            color: #333;
            text-decoration: none;
            border-radius: 4px;
            transition: all 0.3s;
            font-size: 0.95rem;
        }
        .links a:hover {
            background-color: #e0e0e0;
            transform: translateY(-2px);
        }
        .links p {
            color: #999;
            font-style: italic;
        }
        @media (max-width: 768px) {
            .banner {
                height: 180px;
            }
            .banner h1 {
                font-size: 2rem;
            }
            .search {
                margin-bottom: 2rem;
            }
            .category {
                padding: 1.2rem;
            }
            .category h2 {
                font-size: 1.3rem;
            }
            .links a {
                padding: 0.5rem 1rem;
                font-size: 0.9rem;
            }
        }
        @media (max-width: 480px) {
            header {
                padding: 1rem;
            }
            .logo {
                font-size: 1.5rem;
            }
            .time {
                font-size: 0.8rem;
            }
            .banner {
                height: 150px;
            }
            .banner h1 {
                font-size: 1.8rem;
            }
            .search input {
                padding: 0.7rem;
                font-size: 0.9rem;
            }
            .search button {
                padding: 0 1rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <a href="/" class="logo">青和导航</a>
        <div class="time" id="time"></div>
    </header>
    <div class="banner">
        <h1>青和导航</h1>
    </div>
    <div class="search">
        <form action="" method="get">
            <input type="text" name="q" placeholder="搜索导航..." value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
            <button type="submit">搜索</button>
        </form>
    </div>
    <div class="categories">
        <?php if (!empty($searchData)): ?>
            <div class="category">
                <h2>搜索结果: "<?php echo htmlspecialchars($_GET['q']); ?>"</h2>
                <div class="links">
                    <?php if (!empty($searchData)): ?>
                        <?php foreach ($searchData as $item): ?>
                            <a href="<?php echo htmlspecialchars($item['url']); ?>" target="_blank"><?php echo htmlspecialchars($item['name']); ?></a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>没有找到匹配的结果</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $cat): ?>
                    <div class="category">
                        <h2><?php echo htmlspecialchars($cat['name']); ?></h2>
                        <div class="links">
                            <?php if (!empty($cat['data'])): ?>
                                <?php foreach ($cat['data'] as $item): ?>
                                    <a href="<?php echo htmlspecialchars($item['url']); ?>" target="_blank"><?php echo htmlspecialchars($item['name']); ?></a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>该分类暂无数据</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="category">
                    <h2>暂无分类数据</h2>
                    <div class="links">
                        <p>请在数据库中添加分类并启用</p>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <script>
        // 更新北京时间
        function updateTime() {
            const now = new Date();
            // 转换为UTC+8时间
            const utcTime = now.getTime() + now.getTimezoneOffset() * 60000;
            const beijingTime = new Date(utcTime + 8 * 3600000);
            
            // 格式化时间
            const options = {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            };
            const timeStr = beijingTime.toLocaleString('zh-CN', options);
            document.getElementById('time').textContent = timeStr;
        }
        
        // 初始更新和每秒更新
        updateTime();
        setInterval(updateTime, 1000);
    </script>
</body>
</html>