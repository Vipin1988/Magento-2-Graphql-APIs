<?php
namespace Mobile\Customapi\Model;

class HomeProducts implements \Mobile\Customapi\Api\HomeProductsInterface{
protected $_config = null;
    protected $_collection;
    protected $_resource;
    protected $_helper;
    protected $_storeManager;
    protected $_scopeConfig;
    protected $_storeId;
    protected $_storeCode;
    protected $_catalogProductVisibility;
    protected $_objectManager;
    

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Catalog\Model\ResourceModel\Product\Collection $collection,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility,
         \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_objectManager = $objectManager;
        $this->_collection = $collection;
        $this->_resource = $resource;
        $this->_storeManager = $storeManager;
        $this->_catalogProductVisibility = $catalogProductVisibility;
        $this->_storeId=(int)$this->_storeManager->getStore()->getId();
        $this->_storeCode=$this->_storeManager->getStore()->getCode();
    }
	public function products()
	{
        $arrayName=array();
        $topcollection=$this->_bestSellers();
        $arrayName['top_selling_product']['title']='Top Selling Products';
		foreach ($topcollection as $product) {
            $store = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore();
            $productImageUrl = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage();
            $arrayName['top_selling_product']['products'][]= array(
                'id' => $product->getId(),
                'name'=>$product->getName(),
                'product_image'=>$productImageUrl,
                'price'=>$product->getPrice()
            );
        }
        $arrayName['feature_product']['title']='Feature Products';
        $featurecollection=$this->_featuredProducts();
        foreach ($featurecollection as $product) {
            $store = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore();
            $productImageUrl = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage();
            $arrayName['feature_product']['products'][]= array(
                'id' => $product->getId(),
                'name'=>$product->getName(),
                'product_image'=>$productImageUrl,
                'price'=>$product->getPrice()
            );
        }
        $arrayName['new_product']['title']='New Products';
        $newCollection=$this->_newProducts();
        foreach ($newCollection as $product) {
            $store = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface')->getStore();
            $productImageUrl = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA) . 'catalog/product' . $product->getImage();
            $arrayName['new_product']['products'][]= array(
                'id' => $product->getId(),
                'name'=>$product->getName(),
                'product_image'=>$productImageUrl,
                'price'=>$product->getPrice()
            );
        }
		return $arrayName;
	}
    private function _bestSellers(){
        $count = 10;                       
        // $category_id = $this->_getConfig('select_category');
        // !is_array($category_id) && $category_id = preg_split('/[\s|,|;]/', $category_id, -1, PREG_SPLIT_NO_EMPTY);
        $connection  = $this->_resource->getConnection();
        $collection = $this->_objectManager->create('\Magento\Catalog\Model\ResourceModel\Product\Collection');
        $collection->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            // ->addAttributeToSelect($this->_catalogConfig->getProductAttributes())
            ->addUrlRewrite()
            ->setStoreId($this->_storeId)
            ->addAttributeToFilter('is_saleable', ['eq' => 1], 'left');
            
        // if (!empty($category_id) && $category_id){
        //     $collection->joinField(
        //         'category_id',
        //         $connection->getTableName($this->_resource->getTableName('catalog_category_product')),
        //         'category_id',
        //         'product_id=entity_id',
        //         null,
        //         'left'
        //     )->addAttributeToFilter(array(array('attribute' => 'category_id', 'in' => array( $category_id))));
        // }
        $collection->setVisibility($this->_catalogProductVisibility->getVisibleInCatalogIds());
        $collection->getSelect()->distinct(true)->group('e.entity_id');
        $collection->getSelect()
            ->joinLeft(['soi' => $connection->getTableName($this->_resource->getTableName('sales_order_item'))], 'soi.product_id = e.entity_id', ['SUM(soi.qty_ordered) AS ordered_qty'])
            ->join(['order' => $connection->getTableName($this->_resource->getTableName('sales_order'))], "order.entity_id = soi.order_id",['order.state'])
            ->where("order.state <> 'canceled' and soi.parent_item_id IS NULL AND soi.product_id IS NOT NULL")
            ->group('soi.product_id')
            ->order('ordered_qty DESC')
            ->limit($count);
        return $collection;
    }
    private function _newProducts(){
        $count = 10;                       
        // $category_id = $this->_getConfig('select_category');
        // !is_array($category_id) && $category_id = preg_split('/[\s|,|;]/', $category_id, -1, PREG_SPLIT_NO_EMPTY);
        $connection  = $this->_resource->getConnection();
        $collection = $this->_objectManager->create('\Magento\Catalog\Model\ResourceModel\Product\Collection');
        $collection->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            // ->addAttributeToSelect($this->_catalogConfig->getProductAttributes())
            ->addUrlRewrite()
            ->setStoreId($this->_storeId)
            ->addAttributeToFilter('is_saleable',['eq' => 1], 'left');
        $collection->getSelect()->order('created_at  DESC');
        // if (!empty($category_id) && $category_id){
        //     $collection->joinField(
        //         'category_id',
        //         $connection->getTableName($this->_resource->getTableName('catalog_category_product')),
        //         'category_id',
        //         'product_id=entity_id',
        //         null,
        //         'left'
        //     )->addAttributeToFilter(array(array('attribute' => 'category_id', 'in' => array( $category_id))));
        // }
        $collection->setVisibility($this->_catalogProductVisibility->getVisibleInCatalogIds());
        $collection->getSelect()->distinct(true)->group('e.entity_id')->limit($count);
        return $collection;
    }
        private function _featuredProducts(){
        $product_order_by = 'entity_id';
        $product_order_dir = 'desc';
        $count = 10;                       
        // $category_id = $this->_getConfig('select_category');
        // !is_array($category_id) && $category_id = preg_split('/[\s|,|;]/', $category_id, -1, PREG_SPLIT_NO_EMPTY);
        $connection  = $this->_resource->getConnection();
        $collection = $this->_objectManager->create('\Magento\Catalog\Model\ResourceModel\Product\Collection');
        
        $collection->addMinimalPrice()
            ->addFinalPrice()
            ->addTaxPercents()
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('image')
            ->addAttributeToSelect('small_image')
            ->addAttributeToSelect('thumbnail')
            // ->addAttributeToSelect($this->_catalogConfig->getProductAttributes())
            ->addUrlRewrite()
            ->setStoreId($this->_storeId)
            ->addAttributeToFilter('sm_featured', ['eq' => 1], 'left')
            ->addAttributeToFilter('is_saleable', ['eq' => 1], 'left');
        // if (!empty($category_id) && $category_id){
        //     $collection->joinField(
        //         'category_id',
        //         $connection->getTableName($this->_resource->getTableName('catalog_category_product')),
        //         'category_id',
        //         'product_id=entity_id',
        //         null,
        //         'left'
        //     )->addAttributeToFilter(array(array('attribute' => 'category_id', 'in' => array( $category_id))));
        // }   
        $collection->setVisibility($this->_catalogProductVisibility->getVisibleInCatalogIds());
        switch ($product_order_by) {
            case 'entity_id':
            case 'name':
            case 'created_at':
                $collection->setOrder($product_order_by, $product_order_dir);
                break;
            case 'price':
                $collection->getSelect()->order('final_price ' . $product_order_dir . '');
                break;
            case 'random':
                $collection->getSelect()->order(new \Zend_Db_Expr('RAND()'));
                break;
        }
        
        $collection->getSelect()->distinct(true)->group('e.entity_id')->limit($count);
        return $collection; 
    }
}
