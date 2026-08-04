<?php

namespace EslovCustomisation\Customisations;

/**
 * Normalize ACF link arrays on mod-manualinput items to URL strings.
 *
 * LTS Modularity extracted link['url'] before views; current Municipio leaves the
 * ACF array, which fatals in parse_url() when components set href. Migration is
 * rejected because ACF link fields must stay as {title,url,target} in the DB.
 * Remove when upstream Modularity restores the normalize (or accepts ACF arrays).
 */
class ManualInputLinkNormalize
{
    public function __construct()
    {
        add_filter('Modularity/Display/mod-manualinput/viewData', [$this, 'normalizeLinks']);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function normalizeLinks(array $data): array
    {
        if (empty($data['manualInputs']) || !is_array($data['manualInputs'])) {
            return $data;
        }

        foreach ($data['manualInputs'] as $index => $input) {
            if (!is_array($input) || empty($input['link']) || !is_array($input['link'])) {
                continue;
            }

            $data['manualInputs'][$index]['link'] = $input['link']['url'] ?? '';
        }

        return $data;
    }
}
