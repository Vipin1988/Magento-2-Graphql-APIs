<?php
namespace Mobile\Customapi\Model;
use Mageplaza\BannerSlider\Helper\Data as bannerHelper;

class HomeSlider implements \Mobile\Customapi\Api\HomeSliderInterface{
	protected $_jsonHelper;
	protected $helperData;
	public function __construct(
        bannerHelper $helperData
    ) {
    	$this->helperData = $helperData;
	 }
	public function slider()
	{
        $collection = $this->helperData->getBannerCollection(1)->addFieldToFilter('status', 1);
        $arrayName=array();
        foreach ($collection as $banner) {
        	$arrayName[] = array('type' => $banner->getType(),
        		'title'=>$banner->getTitle(),
        		'banner'=>$banner->getUrlBanner(),
        		'image_url'=>$banner->getImageUrl()
        	 );
               
		}
		return $arrayName;
	}
}
