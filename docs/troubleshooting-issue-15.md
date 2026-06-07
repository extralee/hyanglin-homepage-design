# 레거시 홈페이지 이전 트러블슈팅 (Issue #15)

## 1. 개요
기존 KT 클라우드에 존재하던 향린교회 레거시 홈페이지(XpressEngine 기반)를 가비아(Gabia) 우분투 서버로 이전하는 과정에서 발생한 주요 장애와 그 해결 과정을 기록합니다.

## 2. 주요 장애 및 원인

### 2.1. XE 캐시 생성 실패 (Silent Fail)
- **증상**: 관리자 페이지 백지화, 홈페이지 상하단 메뉴 실종.
- **원인**: 새 서버의 MySQL 8.0 환경이 기존 XE 코어(PHP 5.6)의 쿼리와 충돌했습니다. 특히 `ONLY_FULL_GROUP_BY` 모드가 켜져 있어 쿼리가 실패했음에도 에러 로그 없이 조용히 중단(Silent Fail)되었습니다.
- **해결**: 가비아 호스트의 `/etc/mysql/mysql.conf.d/mysqld.cnf` 파일에 `sql_mode="STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION"`를 추가하고 MySQL을 재시작했습니다.

### 2.2. 상/하단 메뉴 캐시 누락에 따른 레이아웃 붕괴
- **증상**: 레이아웃 130과 58884 간 혼동. 상하단 메뉴가 전혀 출력되지 않음.
- **원인**: rsync 백업 당시 `files/cache/menu/` 하위의 파일이 누락되었고, Docker 내의 PHP 환경 문제로 XE 코어의 메뉴 캐시 재생성(`makeCache`)이 실패했습니다.
- **해결**: `xe_menu_item` DB를 직접 읽어 XE가 인식하는 포맷(`stdClass` 구조)으로 캐시를 직접 구워내는 `build_menu_cache.php` 스크립트를 작성하여 해결했습니다.

```php
// build_menu_cache.php
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('__XE__', true);
require './files/config/db.config.php';

$conn = new mysqli($db_info->master_db['db_hostname'], $db_info->master_db['db_userid'], $db_info->master_db['db_password'], $db_info->master_db['db_database'], $db_info->master_db['db_port']);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
$conn->set_charset("utf8mb4");

function buildMenuCache($menu_srl, $conn) {
    $sql = "SELECT * FROM xe_menu_item WHERE menu_srl = $menu_srl ORDER BY parent_srl ASC, listorder ASC";
    $result = $conn->query($sql);
    $items = [];
    while($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    
    $tree = [];
    $ref = [];
    foreach($items as $item) {
        $node = [
            'text' => $item['name'],
            'link' => $item['name'],
            'href' => (strpos($item['url'], 'http') === 0) ? $item['url'] : '/' . $item['url'],
            'open_window' => $item['open_window'],
            'selected' => false,
            'list' => []
        ];
        $ref[$item['menu_item_srl']] = $node;
        
        if ($item['parent_srl'] == 0) {
            $tree[$item['menu_item_srl']] =& $ref[$item['menu_item_srl']];
        } else {
            $ref[$item['parent_srl']]['list'][$item['menu_item_srl']] =& $ref[$item['menu_item_srl']];
        }
    }
    
    $cache_content = "<?php\nif(!defined('__XE__')) exit();\n\$menu = new stdClass();\n\$menu->list = " . var_export($tree, true) . ";\n";
    file_put_contents('./files/cache/menu/' . $menu_srl . '.php', $cache_content);
    echo "Built cache for $menu_srl\n";
}

if (!is_dir('./files/cache/menu')) {
    mkdir('./files/cache/menu', 0777, true);
}

buildMenuCache(65, $conn); // 메인 메뉴
buildMenuCache(79, $conn); // 푸터 메뉴
echo "Done.";
```

### 2.3. 홈페이지 중앙부 (IFRAME) 외부 종속성 발견 (가장 중요)
- **증상**: 레이아웃(`58884`)은 정상이나 홈페이지의 메인 콘텐츠(피아노 슬라이더, 고전 말씀, 12개 아이콘 등)가 누락될 위험 확인.
- **원인**: 메인 페이지 렌더링 시 `<iframe src="http://www.hyanglin.org/contents/main.php">`를 불러오고 있었으나, 마이그레이션된 가비아 서버에는 `contents` 폴더가 전송되지 않았습니다.
- **해결 방안**: 향후 DNS 변경 전, 구서버에서 가비아 서버로 `/home/hr/www/contents/` 디렉터리 전체를 반드시 추가 복사해야 합니다.
