<?php
// ===========================================================================
// Minecraft RCON & Twitter ID連携スクリプト
// 作成者: (当時のあなたの名前/ニックネームを想像して)
// 作成日: 20XX年XX月XX日 (約10年前の日付を想像して)
//
// 頑張ってRCON APIとTwitter APIを叩いてみたスクリプト。
// TwitterのユーザーIDとMinecraftのUUIDを連携する機能を目指した。
// ===========================================================================

// ---------------------------------------------------------------------------
// 1. 設定項目
// ---------------------------------------------------------------------------

// Minecraft RCON設定
define('MC_RCON_HOST', 'your_minecraft_server_ip'); // MinecraftサーバーのIPアドレス
define('MC_RCON_PORT', 25575);                       // RCONポート
define('MC_RCON_PASSWORD', 'your_rcon_password');   // RCONパスワード

// Twitter API設定 (旧式のOAuthライブラリを想定)
define('TWITTER_CONSUMER_KEY', 'YOUR_CONSUMER_KEY');
define('TWITTER_CONSUMER_SECRET', 'YOUR_CONSUMER_SECRET');
define('TWITTER_OAUTH_CALLBACK', 'http://your_domain.com/callback.php'); // Twitter認証後のコールバックURL

// データベース設定 (mysqliを使用)
define('DB_HOST', 'localhost');
define('DB_USER', 'db_user');
define('DB_PASS', 'db_password');
define('DB_NAME', 'minecraft_twitter_db');

// エラー報告レベル
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ---------------------------------------------------------------------------
// 2. ユーティリティ関数（RCON接続）
// ---------------------------------------------------------------------------

/**
 * Minecraft RCONに接続し、コマンドを実行する（簡易版）
 * @param string $command 実行するMinecraftコマンド
 * @return string コマンドの出力結果
 */
function sendRconCommand($command) {
    $socket = @fsockopen(MC_RCON_HOST, MC_RCON_PORT, $errno, $errstr, 3);
    if (!$socket) {
        error_log("RCON接続エラー: $errstr ($errno)");
        return "Error: RCON接続に失敗しました。";
    }

    stream_set_timeout($socket, 1); // タイムアウト設定
    $response = '';

    // RCON認証パケット (簡略化)
    // 実際にはもっと複雑なプロトコルを実装する必要がある
    fwrite($socket, pack('VV', 10, 3) . MC_RCON_PASSWORD . "\0\0"); // 認証リクエスト（簡略化）
    $authResponse = fread($socket, 4096); // 認証応答

    // コマンドパケット (簡略化)
    fwrite($socket, pack('VV', strlen($command) + 10, 2) . $command . "\0\0"); // コマンドリクエスト
    
    // 応答読み込み
    while (!feof($socket)) {
        $chunk = fread($socket, 4096);
        if ($chunk === false || $chunk === '') break;
        $response .= $chunk;
    }
    fclose($socket);

    // RCONプロトコルの応答パケット解析は複雑なので、ここでは単純に文字列として返す（当時の頑張りを想像して）
    // 実際にはパケットサイズ、ID、タイプなどを解析して、適切なコンテンツ部分を取り出す必要がある
    // ここでは、応答からコマンド部分以降のテキストを「それっぽく」抜き出す
    $filteredResponse = preg_replace('/^.{' . (strlen($command) + 8) . '}(.*)/s', '$1', $response); // 適当にパケットヘッダをカットするイメージ
    $filteredResponse = trim($filteredResponse, "\0"); // ヌルバイトを除去
    
    return $filteredResponse;
}


// ---------------------------------------------------------------------------
// 3. データベース接続
// ---------------------------------------------------------------------------

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($mysqli->connect_errno) {
    echo "データベース接続に失敗しました: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
    exit();
}

// テーブル作成（初回実行時のみ、手動でSQL実行していたかも）
// CREATE TABLE IF NOT EXISTS user_links (
//     id INT AUTO_INCREMENT PRIMARY KEY,
//     twitter_id VARCHAR(255) UNIQUE NOT NULL,
//     minecraft_uuid VARCHAR(255) UNIQUE NOT NULL,
//     twitter_screen_name VARCHAR(255) NOT NULL,
//     linked_at DATETIME DEFAULT CURRENT_TIMESTAMP
// );

// ---------------------------------------------------------------------------
// 4. Twitter認証処理 (OAuth 1.0a のフローを想定)
//    - TwitterAPIExchangeライブラリなど外部ライブラリを使っていた可能性が高い
//    - ここでは簡略化してコメントで流れを示す
// ---------------------------------------------------------------------------

/*
if (isset($_GET['action']) && $_GET['action'] == 'twitter_auth') {
    // 1. リクエストトークン取得
    //   - OAuthConsumerKey, OAuthConsumerSecret を使ってリクエストトークンを取得
    //   - ユーザーをTwitter認証画面にリダイレクト (oauth_token と oauth_callback を付与)
    // header('Location: https://api.twitter.com/oauth/authorize?oauth_token=' . $requestToken['oauth_token']);
    // exit();
}

if (isset($_GET['oauth_token']) && isset($_GET['oauth_verifier'])) {
    // 2. アクセストークン取得 (コールバックURLで実行される処理)
    //   - リクエストトークンと oauth_verifier を使ってアクセストークンを取得
    //   - ユーザーの access_token, access_token_secret, twitter_user_id, screen_name が取得できる
    //   - セッションに保存したり、データベースに保存したりする
    
    // ここで取得した $twitter_user_id と $twitter_screen_name をDBに保存する準備
    // セッションに一時保存されたMinecraftのUUIDがあればそれも使う
    $current_minecraft_uuid = $_SESSION['pending_minecraft_uuid'] ?? null;

    if ($current_minecraft_uuid) {
        $stmt = $mysqli->prepare("INSERT INTO user_links (twitter_id, minecraft_uuid, twitter_screen_name) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $twitter_user_id, $current_minecraft_uuid, $twitter_screen_name);
        if ($stmt->execute()) {
            echo "Minecraft IDとTwitter IDの連携が完了しました！";
        } else {
            echo "連携に失敗しました: " . $stmt->error;
        }
        $stmt->close();
        unset($_SESSION['pending_minecraft_uuid']); // 完了したらセッションをクリア
    } else {
        echo "Twitter認証は完了しましたが、連携するMinecraft UUIDがありません。";
    }
}
*/

// ---------------------------------------------------------------------------
// 5. メイン処理 (例: Minecraftのプレイヤーリスト表示と連携リンク)
// ---------------------------------------------------------------------------

echo "<h1>Minecraftサーバー情報とTwitter連携</h1>";

// RCONでプレイヤーリストを取得
$players_raw = sendRconCommand('list'); // 'list' コマンドでプレイヤー一覧を取得
echo "<h2>現在のオンラインプレイヤー:</h2>";
echo "<pre>" . htmlspecialchars($players_raw) . "</pre>";

// RCON応答の解析はかなり難しいので、ここでは単純な応答を前提とする
// 例: "There are 2/20 players online: Player1, Player2"

if (preg_match('/There are (\d+)\/(\d+) players online: (.*)/', $players_raw, $matches)) {
    $online_count = $matches[1];
    $player_names = explode(', ', $matches[3]);

    if ($online_count > 0) {
        echo "<ul>";
        foreach ($player_names as $player_name) {
            echo "<li>" . htmlspecialchars($player_name);
            
            // このプレイヤーのUUIDを取得するRCONコマンドは当時難しかったかも
            // 'uuid' コマンドは最近のバージョンでしか使えない
            // なので、UUIDは別途取得するロジックが必要だったと想像
            
            // データベースに連携済みかチェック
            $stmt = $mysqli->prepare("SELECT twitter_screen_name FROM user_links WHERE minecraft_uuid = ?");
            // $player_uuid は RCONでは取得が困難なため、ここでは仮の値
            $dummy_player_uuid = md5($player_name); // 仮のUUID生成（実際はゲーム内コマンドなどで取得）
            $stmt->bind_param("s", $dummy_player_uuid);
            $stmt->execute();
            $stmt->bind_result($linked_screen_name);
            $stmt->fetch();

            if ($linked_screen_name) {
                echo " (連携済み: @<a href='https://twitter.com/" . htmlspecialchars($linked_screen_name) . "' target='_blank'>" . htmlspecialchars($linked_screen_name) . "</a>)";
            } else {
                // 連携用リンク (Twitter認証に飛ばす)
                // この時、MinecraftのUUIDをセッションに一時保存しておき、
                // Twitter認証完了後にデータベースに登録する想定
                // $_SESSION['pending_minecraft_uuid'] = $dummy_player_uuid;
                echo " - <a href='?action=twitter_auth'>Twitter連携する</a>";
            }
            $stmt->close();
            echo "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>現在、オンラインプレイヤーはいません。</p>";
    }
} else {
    echo "<p>プレイヤーリストの取得に失敗しました。</p>";
}

$mysqli->close();
?>