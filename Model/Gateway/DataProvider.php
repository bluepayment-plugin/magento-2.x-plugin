<?php
declare(strict_types=1);

namespace BlueMedia\BluePayment\Model\Gateway;

use BlueMedia\BluePayment\Model\ConfigProvider;
use BlueMedia\BluePayment\Model\ResourceModel\Gateway\CollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Ui\DataProvider\AbstractDataProvider;

class DataProvider extends AbstractDataProvider
{
    /** @var AbstractCollection */
    protected $collection;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var array
     */
    protected $loadedData;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @inheritDoc
     */
    public function getData(): array
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        $items = $this->collection->getItems();
        foreach ($items as $model) {
            $gatewayId = (int) $model->getData('gateway_id');

            $this->loadedData[$model->getId()] = $model->getData();
            $this->loadedData[$model->getId()]['always_separated'] = $this->isAlwaysSeparated($gatewayId);
            $this->loadedData[$model->getId()]['static_name'] = $this->hasStaticName($gatewayId);
        }
        $data = $this->dataPersistor->get('bluemedia_bluepayment_gateway');

        if (!empty($data)) {
            $model = $this->collection->getNewEmptyItem();
            $model->setData($data);
            $this->loadedData[$model->getId()] = $model->getData();
            $this->dataPersistor->clear('bluemedia_bluepayment_gateway');
        }

        return $this->loadedData;
    }

    /**
     * Verify if gateway must be always separated.
     *
     * @param int $gatewayId
     *
     * @return bool
     */
    protected function isAlwaysSeparated(int $gatewayId): bool
    {
        return array_contains(ConfigProvider::ALWAYS_SEPARATED, $gatewayId);
    }

    /**
     * Check if gateway has static name.
     *
     * @param  int  $gatewayId
     *
     * @return bool
     */
    protected function hasStaticName(int $gatewayId): bool
    {
        return array_contains(ConfigProvider::STATIC_GATEWAY_NAME, $gatewayId);
    }
}

