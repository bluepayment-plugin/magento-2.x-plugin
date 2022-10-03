<?php
declare(strict_types=1);

namespace BlueMedia\BluePayment\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;

class Logo extends Column
{
    private const ID = 'id';
    private const URL = 'gateway_logo_url';

    /**
     * @inheritdoc
     */
    public function prepare(): void
    {
        parent::prepare();
        $this->setData(
            'config',
            array_replace_recursive(
                (array) $this->getData('config'),
                [
                    'fields' => [
                        'id' => self::ID,
                        'url' => self::URL
                    ]
                ]
            )
        );
    }
}
