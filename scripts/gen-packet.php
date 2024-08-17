<?php

/**
 * @throws Exception
 */
function generateCustomPacket($packetName, $additionalProperties = []): string
{
    $namespace = "Supero\\NightfallProtocol\\network\\packets";
    $basePacket = "pocketmine\\network\\mcpe\\protocol\\$packetName as PM_Packet";
    $customPacket = "$packetName extends PM_Packet";

    // Load the original file
    $originalFilePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'pocketmine' . DIRECTORY_SEPARATOR . 'bedrock-protocol' . DIRECTORY_SEPARATOR . "src" . DIRECTORY_SEPARATOR . "$packetName.php";
    if (!file_exists($originalFilePath)) {
        throw new Exception("Original file not found: $originalFilePath");
    }
    $originalContent = file_get_contents($originalFilePath);

    // Extract properties from original content
    preg_match_all('/public (.+?) \$(\w+);/', $originalContent, $matches);
    //preg_match_all('/private (.+?) \$(\w+);/', $originalContent, $matches);

    $properties = [];
    foreach ($matches[0] as $index => $match) {
        $properties[] = ['type' => trim($matches[1][$index]), 'name' => trim($matches[2][$index]), 'option' => "add"];
    }

    // Add additional properties
    foreach ($additionalProperties as $prop) {
        $properties[] = [
            'type' => $prop['type'],
            'name' => $prop['property'],
            'option' => $prop['option'],
            'version' => $prop['version'] ?? null // Use null if version is not set
        ];
    }

    $classString = "<?php\n\nnamespace $namespace;\n\nuse $basePacket;\nuse pocketmine\\network\\mcpe\\protocol\\serializer\\PacketSerializer;\n\nuse Supero\\NightfallProtocol\\network\\CustomProtocolInfo;\n\nclass $customPacket {\n";

    // Add properties
    foreach ($properties as $property) {
        if($property['option'] == "add"){
            $classString .= "    public " . $property['type'] . " \${$property['name']};\n";
        }
    }

    // Create constructor
    $classString .= "\n    /**\n     * @generate-create-func\n     */\n";
    $classString .= "    public static function createPacket(";
    $constructorArgs = [];
    foreach ($properties as $property) {
        if($property['option'] == "add") {
            $constructorArgs[] = "{$property['type']} \${$property['name']}";
        }
    }
    $classString .= implode(', ', $constructorArgs) . ") : self {\n";
    $classString .= "        \$result = new self;\n";

    foreach ($properties as $property) {
        if($property['option'] == "add"){
            $classString .= "        \$result->{$property['name']} = \${$property['name']};\n";
        }
    }

    $classString .= "        return \$result;\n    }\n";

    // Decode payload
    $classString .= "    protected function decodePayload(PacketSerializer \$in) : void {\n";
    foreach ($properties as $property) {
        if (isset($property['version'])) {
            $classString .= "       if (\$in->getProtocol() {$property['version']}) {\n";
        }
        if (isset($property['version'])) {
            $classString .= "           \$this->{$property['name']} = \$in->get" . ucfirst($property['type']) . "();\n";
            $classString .= "       }\n";
        } else {
            $classString .= "       \$this->{$property['name']} = \$in->get" . ucfirst($property['type']) . "();\n";
        }
    }
    $classString .= "    }\n";

    // Encode payload
    $classString .= "    protected function encodePayload(PacketSerializer \$out) : void {\n";
    foreach ($properties as $property) {
        if (isset($property['version'])) {
            $classString .= "       if (\$out->getProtocol() {$property['version']}) {\n";
        }
        if (isset($property['version'])) {
            $classString .= "           \$out->put" . ucfirst($property['type']) . "(\$this->{$property['name']});\n";
            $classString .= "       }\n";
        } else {
            $classString .= "       \$out->put" . ucfirst($property['type']) . "(\$this->{$property['name']});\n";
        }
    }
    $classString .= "    }\n";

    // Get constructor arguments
    $classString .= "    public function getConstructorArguments(PM_Packet \$packet): array {\n";
    $classString .= "        return [\n";
    foreach ($properties as $property) {
        if($property['option'] == "add") {
            $classString .= "            \$packet->get" . ucfirst($property['name']) . "(),\n";
        }
    }
    $classString .= "        ];\n    }\n}";

    return $classString;
}

// Save output to a file
function saveToFile($path, $content): void
{
    file_put_contents($path, $content);
}

// Example usage
try {
    $packetName = 'MobArmorEquipmentPacket';

    $packetContent = generateCustomPacket($packetName, [
        ["type" => "ItemStackWrapper", "property" => "body", "option" => "already-set", "version" => ">= CustomProtocolInfo::PROTOCOL_1_21_20"],
    ]);

    $outputPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Supero' . DIRECTORY_SEPARATOR . 'NightfallProtocol' . DIRECTORY_SEPARATOR . "network" . DIRECTORY_SEPARATOR . "packets" .  DIRECTORY_SEPARATOR . $packetName . ".php";

    saveToFile($outputPath, $packetContent);

    echo "Packet generated and saved to: $outputPath\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}