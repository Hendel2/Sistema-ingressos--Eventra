<?php
declare(strict_types=1);

/**
 * Estados e municípios brasileiros.
 *
 * Os municípios vêm da base do IBGE. Como o host oficial da API
 * (servicodedados.ibge.gov.br) fica indisponível com alguma frequência, a
 * consulta tenta o IBGE primeiro e cai para a BrasilAPI, que republica os
 * mesmos dados. O resultado é gravado em cache no disco, então o cadastro
 * continua funcionando mesmo com as duas fontes fora do ar.
 */
class LocationService
{
    /** Tempo até o cache ser considerado velho (30 dias). */
    private const CACHE_TTL = 2592000;

    private const SOURCES = [
        'https://servicodedados.ibge.gov.br/api/v1/localidades/estados/%s/municipios',
        'https://brasilapi.com.br/api/ibge/municipios/v1/%s',
    ];

    /** As 27 unidades federativas. */
    public static function states(): array
    {
        return [
            'AC' => 'Acre',
            'AL' => 'Alagoas',
            'AP' => 'Amapá',
            'AM' => 'Amazonas',
            'BA' => 'Bahia',
            'CE' => 'Ceará',
            'DF' => 'Distrito Federal',
            'ES' => 'Espírito Santo',
            'GO' => 'Goiás',
            'MA' => 'Maranhão',
            'MT' => 'Mato Grosso',
            'MS' => 'Mato Grosso do Sul',
            'MG' => 'Minas Gerais',
            'PA' => 'Pará',
            'PB' => 'Paraíba',
            'PR' => 'Paraná',
            'PE' => 'Pernambuco',
            'PI' => 'Piauí',
            'RJ' => 'Rio de Janeiro',
            'RN' => 'Rio Grande do Norte',
            'RS' => 'Rio Grande do Sul',
            'RO' => 'Rondônia',
            'RR' => 'Roraima',
            'SC' => 'Santa Catarina',
            'SP' => 'São Paulo',
            'SE' => 'Sergipe',
            'TO' => 'Tocantins',
        ];
    }

    public static function isValidState(string $uf): bool
    {
        return isset(self::states()[strtoupper($uf)]);
    }

    /**
     * Todos os municípios da UF, em ordem alfabética.
     * Devolve lista vazia apenas se não houver rede nem cache.
     */
    public static function cities(string $uf): array
    {
        $uf = strtoupper(trim($uf));
        if (!self::isValidState($uf)) {
            return [];
        }

        $cached = self::readCache($uf);
        if ($cached !== null && !$cached['stale']) {
            return $cached['cities'];
        }

        $fresh = self::fetch($uf);
        if ($fresh !== []) {
            self::writeCache($uf, $fresh);
            return $fresh;
        }

        // Rede falhou: um cache vencido ainda é melhor que lista vazia.
        return $cached['cities'] ?? [];
    }

    /** Baixa a lista de municípios, tentando cada fonte na ordem. */
    private static function fetch(string $uf): array
    {
        foreach (self::SOURCES as $template) {
            $body = self::request(sprintf($template, $uf));
            if ($body === null) {
                continue;
            }

            $data = json_decode($body, true);
            if (!is_array($data)) {
                continue;
            }

            $names = [];
            foreach ($data as $item) {
                // IBGE e BrasilAPI usam a mesma chave "nome".
                $name = is_array($item) ? ($item['nome'] ?? null) : null;
                if (is_string($name) && $name !== '') {
                    $names[] = self::humanize($name);
                }
            }

            if ($names !== []) {
                $names = array_values(array_unique($names));
                usort($names, static fn(string $a, string $b): int => strcoll($a, $b) ?: strcmp($a, $b));
                return $names;
            }
        }

        return [];
    }

    private static function request(string $url): ?string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_USERAGENT => 'Eventra/1.0',
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($body !== false && $code === 200) ? (string) $body : null;
    }

    /**
     * A API devolve os nomes em caixa alta ("SAO PAULO", "AGUAÍ").
     * Aqui viram "São Paulo", "Aguaí", "Santa Bárbara d'Oeste".
     */
    private static function humanize(string $name): string
    {
        $connectors = ['de', 'da', 'do', 'das', 'dos', 'e'];
        $words = preg_split('/\s+/u', mb_strtolower(trim($name), 'UTF-8')) ?: [];
        $out = [];

        foreach ($words as $i => $word) {
            if ($i > 0 && in_array($word, $connectors, true)) {
                $out[] = $word;
                continue;
            }
            if (mb_strpos($word, "'") !== false) {
                [$before, $after] = explode("'", $word, 2);
                $out[] = $before . "'" . mb_convert_case($after, MB_CASE_TITLE, 'UTF-8');
                continue;
            }
            $out[] = mb_convert_case($word, MB_CASE_TITLE, 'UTF-8');
        }

        return implode(' ', $out);
    }

    private static function cacheFile(string $uf): string
    {
        return APP_ROOT . '/storage/cache/cities/' . $uf . '.json';
    }

    /** @return array{cities: array, stale: bool}|null */
    private static function readCache(string $uf): ?array
    {
        $file = self::cacheFile($uf);
        if (!is_file($file)) {
            return null;
        }

        $data = json_decode((string) file_get_contents($file), true);
        if (!is_array($data) || empty($data['cities'])) {
            return null;
        }

        return [
            'cities' => $data['cities'],
            'stale' => (time() - (int) ($data['saved_at'] ?? 0)) > self::CACHE_TTL,
        ];
    }

    private static function writeCache(string $uf, array $cities): void
    {
        $dir = dirname(self::cacheFile($uf));
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            return;
        }

        @file_put_contents(
            self::cacheFile($uf),
            json_encode(['saved_at' => time(), 'cities' => $cities], JSON_UNESCAPED_UNICODE)
        );
    }
}
