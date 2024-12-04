<?php

function fetchFileFromRepo($fileUrl): ?string
{
    $ch = curl_init($fileUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/vnd.github.v3.raw',
        'User-Agent: PHP Script'
    ]);

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $content = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Curl error: ' . curl_error($ch);
        return null;
    }

    curl_close($ch);
    return $content;
}

function modifyPacketContent($content, $packet): string
{
    // Removes the header
    $content = preg_replace('/\/\*.*?\*\//s', '', $content);

    $content = str_replace('namespace pocketmine\network\mcpe\protocol;', 'namespace Supero\NightfallProtocol\network\packets;', $content);
    $content = str_replace('getProtocolId()', 'getProtocol()', $content);
    $content = str_replace('ProtocolInfo', 'CustomProtocolInfo', $content);
    $content = str_replace("class $packet extends DataPacket", "use pocketmine\\network\mcpe\protocol\\$packet as PM_Packet;" . PHP_EOL . "use Supero\NightfallProtocol\\network\CustomProtocolInfo;" . PHP_EOL . "class $packet extends PM_Packet", $content);
    $content = preg_replace('/public const NETWORK_ID = .*?;\n/', '', $content);
    $content = str_replace('public static function create', 'public static function createPacket', $content);

    $getConstructorArgs = <<<EOD

    public function getConstructorArguments(PM_Packet \$packet): array
    { //TODO 
    }
EOD;

    $lastBracePos = strrpos($content, '}');
    return substr_replace($content, $getConstructorArgs . "\n", $lastBracePos, 0);
}

function saveToFile($path, $content): void
{
    file_put_contents($path, $content);
}

$packet = "ItemStackResponseSlotInfo";

$fileUrl = "https://api.github.com/repos/NetherGamesMC/BedrockProtocol/contents/src/$packet.php";

$content = fetchFileFromRepo($fileUrl);

if ($content !== null) {
    $modifiedContent = modifyPacketContent($content, $packet);
    echo $modifiedContent;
    $outputPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Supero' . DIRECTORY_SEPARATOR . 'NightfallProtocol' . DIRECTORY_SEPARATOR . "network" . DIRECTORY_SEPARATOR . "packets" .  DIRECTORY_SEPARATOR . $packet . ".php";
    saveToFile($outputPath, $modifiedContent);
} else {
    echo "Failed to fetch the file.";
}