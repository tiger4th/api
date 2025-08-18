<?php
require_once __DIR__ . '/config.php';

// リクエストメソッドを確認
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed. Use POST or GET.']);
    exit();
}

// パラメータを取得（POST優先、なければGET）
$content = $_POST['content'] ?? $_GET['content'] ?? '';

// パラメータの検証
if (empty(trim($content))) {
    http_response_code(400);
    echo json_encode(['error' => 'content parameter is required']);
    exit();
}

// ココナラの出品禁止チェック用プロンプト
$prompt = <<<EOD
下記はココナラのサービスページの記載内容です。
ココナラの出品禁止サービスに該当するかどうかチェックしてください。
---
{$content}
---

以下はココナラの出品禁止行為です。
---
- 資格を要する相談サービスで、資格を有しない出品者である場合
  - 士業資格
  - その他資格
- 景品表示法違反に該当する表現
  - 効果に関する断定表現や誇大表現の記載
  - 運営にて事実確認をすることができないと判断される実績の記載
  - 期間や限定数を明示することなく、値下げやキャンペーン中、あるいは数量限定販売である旨を表示している記載
  - 効果に関する具体的な数値や倍率の記載
  - 成功事例を抜粋して表示したり、成功事例のみしかない旨の記載
  - 運営にて事実確認ができない販売価格との比較をしている記載
  - 根拠・実績のない最上級表現の記載
  - 効果の永続を謳う表現の記載
  - 比較対象を明示した上で優位性を示す記載
- ココナラ外での取引を促しているサービス
  - 直接やり取り可能なサイトの情報を掲載しているサービス
  - ココナラ外の連絡手段を用いてコンテンツ提供を行うサービス
  - 外部サイトへの誘導しているサービス
  - ココナラ外で連絡可能な手段が掲載されるおそれのあるサイトでのやりとりが発生するサービス
- 他社サービスの規約違反となるサービス
  - 他社サービスのアカウントの販売や譲渡をしているサービス
  - 口コミ（レビュー）の投稿を代行し、意図的な情報操作に協力するサービス
  - SNSや動画サイトなどの情報（フォロワー、いいね、チャンネル登録数、再生数など）を不当に操作する恐れがあるサービス
  - 無在庫販売や出品代行を指南しているサービス
  - SMS認証を代行しているサービス
  - ソフトウェアのライセンスに違反する役務提供を行うと判断されるサービス
- 公序良俗に反するサービス
  - 学校の課題（宿題、レポート等）を代行するサービス
  - 成人向け、アダルト関連、その他青少年に有害な情報の送信を行うと判断されるサービス
  - 第三者に呪い、復讐などで不幸をもたらすことを前提としているサービス
  - 不倫成就・助長にあたる内容により第三者に不幸をもたらす恐れがあるサービス
  - 自身の利益を得るためにアプリ登録などを強要・誘導する行為
  - ブラックリストの人向けの融資や資金調達のノウハウを提供を行うサービス
  - 政治関連動画など、当社の判断で不適当とみなすサービス
- 知的財産権、著作権等を侵害するサービス
  - 他社の商標を不正に掲載しているサービス
  - 第三者が著作権を有する著作物を許可なく複製・転用・不正利用したサービス
  - パブリシティ権、肖像権を侵害する恐れがあるサービス
  - 商用利用が禁止されている素材を利用したサービス
  - 無料で公開されているweb上の情報を、公開元の承諾を得ずに他者に提供するサービス
  - 他者の出品サービスをコピーして利用する行為（他の会員の写真や文章の無断使用）
  - AIによるイラスト作成を行うサービス
- 医療行為に該当するサービス
  - 病気を治すなど身体に影響を与えることを前提としているサービス
  - 病気に効く薬を紹介しているサービス
  - 特定の症状に効能を持つ食品やサプリを紹介しているサービス
- 投資・FX・副業に関する問題のあるサービス
  - 特定の金融商品を勧めていたり、売買タイミングを指南しているサービス
  - 投資判断に関するアドバイスを行うサービス
  - 投資運用や資産運用を代行しているサービス
  - 暗号資産交換業に該当するサービス
  - オンラインカジノやブックメーカーなどの利用を推奨している又は利用方法を指南しているサービス
  - 日本国内の証拠金倍率（レバレッジ）の上限25倍を超えるサービス
  - 金融商品取引法に基づく登録を受けていない海外所在業者との取引を推奨している又は取引方法を指南しているサービス
- 占い・施術・ヒーリングに関する問題のあるサービス
  - 購入者の悩みや状況に応じた「鑑定・アドバイス・フィードバック」を行うことが記載されてない施術サービス
  - 身体に直接影響を与えると判断されるサービス
  - モノに対する施術を行われるサービス
  - 第三に呪い、復讐などで不幸をもたらすことを前提としているサービス
  - 能力伝授を行うと判断されるサービス
  - 反復して購入することで効果の増幅をうたう表現が含まれるサービス
  - 追加購入することで効果の増幅をうたう表現が含まれるサービス
  - 占い・スピリチュアル全般に関するグッズの販売・発送を行うサービス
- 旅行業務に該当するサービス
  - 購入者の旅行のために、出品者自身の名義もしくは購入者の代理で宿や飛行機などの予約をするサービス
  - ホテルや民宿などの宿泊手段を紹介するサービス
  - 飛行機やフェリー、新幹線などの旅客輸送手段を紹介するサービス
- 会うことを前提としているサービス
  - 直接会うことが前提となっているサービス
  - 直接会うことを持ちかけているサービス
- 出張撮影・出張サービスカテゴリに関する問題のあるサービス
  - オンライン取引カテゴリの内容を出張撮影・出張サービスカテゴリで出品しているサービス
  - 事前打ち合わせで直接会う恐れのあるサービス
  - 撮影対象が不透明であり、具体的な提供内容や取引の流れが記載されてない撮影サービス
  - 水着モデル撮影やヌードモデル撮影など露出が多い撮影サービス
  - 食品衛生法に抵触する行為が含まれる恐れのあるサービス
  - 主な役務提供が施術ではなくレッスン・指導行為と判断されるサービス
  - 会場でのおひねりを誘導するなど直接決済の恐れがあるサービス
- 不適合な出品方法で出品しているサービス
  - サービスと適合しないカテゴリに出品する行為
  - サービスと適合しない提供形式で出品する行為
  - 同一のサービスを複数出品する行為
  - 第三の出品を代行する行為
  - 物品配送可能サービスでないにもかかわらず、配送が役務内容となっているサービス
  - 買い物代行など、既製品を代行して購入し配送するサービス
  - 食品や肌に直接触れるもの・刃物などを配送するサービス
  - おひねりやオプションの支払いが前提となっているサービス
  - 設定されている価格と表記されている価格が異なるサービス
  - 最低サービス価格を下回る価格で提供する恐れがあるサービス
  - 無料で役務提供を行うと判断されるサービス
  - 返金保証を行うと判断されるサービス
  - ココナラのランクについて事実と異なる記載をしているサービス
  - トークルームの提供期限（120日）を超える恐れのあるサービス
  - 無期限のサポートもしくはそれに準ずる役務提供を行うと判断されるサービス
- 出品を目的としないサービス
  - 仕事の依頼をする行為
  - 営業活動、宣伝活動、宗教活動、選挙活動にあたる行為
  - マルチ、ねずみ講、MLMなどビジネス勧誘行為
  - 職業の紹介や派遣、斡旋にあたる行為
---

上記の禁止事項に該当するかどうかを確認し、以下の形式で回答してください。
回答にはmarkdownを使用しないでください。

【判定結果】
禁止事項に該当する/禁止事項に該当しない

【該当する項目】
項目: 具体的な理由

【総合的な判断】
詳細な説明
EOD;

// Gemini APIにリクエストを送信する関数
function callGeminiAPI($prompt) {
    $url = GEMINI_API_URL . '?key=' . GEMINI_API_KEY;
    
    $data = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.9,
            'topK' => 1,
            'topP' => 1,
            'maxOutputTokens' => 2048,
            'stopSequences' => []
        ],
        'safetySettings' => [
            [
                'category' => 'HARM_CATEGORY_HARASSMENT',
                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
            ],
            [
                'category' => 'HARM_CATEGORY_HATE_SPEECH',
                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
            ],
            [
                'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
            ],
            [
                'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
            ]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        throw new Exception("cURL Error: " . $error);
    }

    return [
        'status' => $httpCode,
        'response' => json_decode($response, true)
    ];
}

try {
    // 環境変数の確認
    if (!defined('GEMINI_API_KEY') || GEMINI_API_KEY === 'YOUR_API_KEY_HERE') {
        throw new Exception('APIキーが正しく設定されていません。env.phpを確認してください。');
    }

    $result = callGeminiAPI($prompt);
    
    if ($result['status'] === 200) {
        $text = $result['response']['candidates'][0]['content']['parts'][0]['text'] ?? 'No response text found';
        echo json_encode([
            'status' => 'success',
            'response' => $text
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'error' => 'API request failed'
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error' => 'Internal server error'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
