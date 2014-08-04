<?php
/**
 * Functional tests for shopping cart ajax
 *
 * @author Mark Guinn <mark@adaircreative.com>
 * @date 04.07.2014
 * @package shop
 * @subpackage tests
 */
class ShoppingCartAjaxTest extends FunctionalTest {

	protected static $fixture_file = 'shop/tests/fixtures/shop.yml';
	protected static $disable_themes = true;
	protected static $use_draft_site = false;

	protected $autoFollowRedirection = false;

	protected $ajaxHeaders = array('X-Requested-With' => 'XMLHttpRequest');

	public function setUpOnce() {
		if (!ShoppingCart_Controller::has_extension('ShoppingCartAjax')) ShoppingCart_Controller::add_extension('ShoppingCartAjax');
		if (!VariationForm::has_extension('ShoppingCartAjax')) VariationForm::add_extension('ShoppingCartAjax');
		if (!Controller::has_extension('AjaxControllerExtension')) Controller::add_extension('AjaxControllerExtension');
		parent::setUpOnce();
	}

	public function setUp() {
		parent::setUp();
		ShopTest::setConfiguration(); //reset config

		$this->mp3player = $this->objFromFixture('Product', 'mp3player');
		$this->socks     = $this->objFromFixture('Product', 'socks');

		//products that can't be purchased
		$this->noPurchaseProduct = $this->objFromFixture('Product', 'beachball');
		$this->draftProduct      = $this->objFromFixture('Product', 'tshirt');
		$this->noPriceProduct    = $this->objFromFixture('Product', 'hdtv');

		//publish some products
		$this->mp3player->publish('Stage','Live');
		$this->socks->publish('Stage','Live');
		$this->noPurchaseProduct->publish('Stage','Live');
		$this->noPriceProduct->publish('Stage','Live');
		//note that we don't publish 'tshirt'... we want it to remain in draft form.

		$this->cart = ShoppingCart::singleton();
		$this->cart->clear();
	}

	public function testAddToCart() {
		// test non-ajax request
		$r = $this->get(ShoppingCart_Controller::add_item_link($this->mp3player));
		$this->assertFalse($r instanceof AjaxHTTPResponse);
		$this->assertEquals(302, $r->getStatusCode());

		// test ajax request
		$r = $this->get(ShoppingCart_Controller::add_item_link($this->mp3player), null, $this->ajaxHeaders);
		$this->assertTrue($r instanceof AjaxHTTPResponse);
		$this->assertEquals(200, $r->getStatusCode());
		$data = json_decode($r->getBody(), true);
		$this->assertNotEmpty($data[AjaxHTTPResponse::EVENTS_KEY]);
		$this->assertNotEmpty($data[AjaxHTTPResponse::REGIONS_KEY]['SideCart']);

		$r = $this->get(ShoppingCart_Controller::add_item_link($this->socks), null, $this->ajaxHeaders);
		$this->assertTrue($r instanceof AjaxHTTPResponse);

		// See what's in the cart
		$items = ShoppingCart::curr()->Items();
		$this->assertNotNull($items);
		$this->assertEquals($items->Count(), 2, 'There are 2 items in the cart');
		//join needed to provide ProductID
		$mp3playeritem = $items->innerJoin("Product_OrderItem","\"OrderItem\".\"ID\" = \"Product_OrderItem\".\"ID\"")->find('ProductID',$this->mp3player->ID);
		$this->assertNotNull($mp3playeritem, "Mp3 player is in cart");

		// We have the product that we asserted in our fixture file, with a quantity of 2 in the cart
		$this->assertEquals($mp3playeritem->ProductID, $this->mp3player->ID, 'We have the correct Product ID in the cart.');
		$this->assertEquals($mp3playeritem->Quantity, 2, 'We have 2 of this product in the cart.');

		// set item quantiy
		$r = $this->get(ShoppingCart_Controller::set_quantity_item_link($this->mp3player,array('quantity' => 5)), null, $this->ajaxHeaders);
		$items = ShoppingCart::curr()->Items();
		$this->assertEquals(200, $r->getStatusCode());
		$data = json_decode($r->getBody(), true);
		$this->assertNotEmpty($data[AjaxHTTPResponse::EVENTS_KEY]);
		$this->assertEquals(5, $data[AjaxHTTPResponse::EVENTS_KEY]['cartchange']['quantity']);
		$mp3playeritem = $items->innerJoin("Product_OrderItem","\"OrderItem\".\"ID\" = \"Product_OrderItem\".\"ID\"")->find('ProductID',$this->mp3player->ID); //join needed to provide ProductID
		$this->assertEquals($mp3playeritem->Quantity, 5, 'We have 5 of this product in the cart.');

		// non purchasable product checks
//		$this->assertEquals($this->noPurchaseProduct->canPurchase(),false,'non-purcahseable product is not purchaseable');
//		$this->assertArrayNotHasKey($this->noPurchaseProduct->ID,$items->map('ProductID')->toArray(),'non-purcahable product is not in cart');
//		$this->assertEquals($this->draftProduct->canPurchase(),false,'draft product is not purchaseable');
//		$this->assertArrayNotHasKey($this->draftProduct->ID,$items->map('ProductID')->toArray(),'draft product is not in cart');
//		$this->assertEquals($this->noPriceProduct->canPurchase(),false,'product without price is not purchaseable');
//		$this->assertArrayNotHasKey($this->noPriceProduct->ID,$items->map('ProductID')->toArray(),'product without price is not in cart');
	}

	public function testRemoveFromCart() {
		// add items via url
		$this->get(ShoppingCart_Controller::set_quantity_item_link($this->mp3player,array('quantity' => 5)));
		$this->get(ShoppingCart_Controller::add_item_link($this->socks));

		// remove items via url
		$r = $this->get(ShoppingCart_Controller::remove_item_link($this->socks), null, $this->ajaxHeaders); //remove one different = remove completely
		$data = json_decode($r->getBody(), true);
		$this->assertNotEmpty($data[AjaxHTTPResponse::EVENTS_KEY]['cartremove']);
		$this->assertFalse($this->cart->get($this->socks));

		$r = $this->get(ShoppingCart_Controller::remove_item_link($this->mp3player)); //remove one product = 4 left
		$this->assertFalse($r instanceof AjaxHTTPResponse);

		$mp3playeritem = $this->cart->get($this->mp3player);
		$this->assertTrue($mp3playeritem !== false,"product still exists");
		$this->assertEquals($mp3playeritem->Quantity,4,"only 4 of item left");

		$items = ShoppingCart::curr()->Items();
		$this->assertNotNull($items,"Cart is not empty");

		$this->cart->clear(); //test clearing cart
		$this->assertEquals(ShoppingCart::curr(), null, 'Cart is clear'); //items is a databoject set, and will therefore be null when cart is empty.
	}

	public function testVariations(){
		$this->loadFixture('shop/tests/fixtures/variations.yml');
		$ballRoot = $this->objFromFixture('Product', 'ball');
		$ballRoot->publish('Stage','Live');
		$ball1 = $this->objFromFixture('ProductVariation', 'redlarge');
		$ball2 = $this->objFromFixture('ProductVariation', 'redsmall');

		// Add the two variation items
		$r = $this->get(ShoppingCart_Controller::add_item_link($ball1), null, $this->ajaxHeaders);
		$this->assertTrue($r instanceof AjaxHTTPResponse);
		$r = $this->get(ShoppingCart_Controller::add_item_link($ball2));
		$this->assertFalse($r instanceof AjaxHTTPResponse);
		$items = ShoppingCart::curr()->Items();
		$this->assertNotNull($items);
		$this->assertEquals($items->Count(), 2,          'There are 2 items in the cart');

		// Remove one and see what happens
		$r = $this->get(ShoppingCart_Controller::remove_all_item_link($ball1), null, $this->ajaxHeaders);
		$this->assertTrue($r instanceof AjaxHTTPResponse);
		$this->assertEquals($items->Count(), 1,          'There is 1 item in the cart');
		$this->assertFalse($this->cart->get($ball1),     "first item not in cart");
		$this->assertNotNull($this->cart->get($ball1),   "second item is in cart");
	}


//	public function testVariationForm() {
//		$this->loadFixture('shop/tests/fixtures/variations.yml');
//		$ballRoot = $this->objFromFixture('Product', 'ball');
//		$ballRoot->publish('Stage','Live');
//		$ball1 = $this->objFromFixture('ProductVariation', 'redlarge');
//		$ball2 = $this->objFromFixture('ProductVariation', 'redsmall');
//
//		$url = $ballRoot->Link() . 'Form';
//		echo "url=$url\n";
//		$r = $this->post($url, array(
//			'ProductAttributes[1]'  => 3,   // size: large
//			'ProductAttributes[2]'  => 4,   // color: red
//			'Quantity'              => 1,
//			'action_addtocart'      => 'Add to Cart',
//		), $this->ajaxHeaders);
//
//		Debug::dump($r);
//		$this->assertTrue($r instanceof AjaxHTTPResponse);
//		$items = ShoppingCart::curr()->Items();
//		$this->assertNotNull($items);
//		$this->assertEquals($items->Count(), 1,          'There is 1 item in the cart');
//		$this->assertTrue($this->cart->get($ball1) instanceof ProductVariation_OrderItem, "first item is correct");
//	}


}