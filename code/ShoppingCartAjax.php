<?php
/**
 * Ajax-specific functionality for shopping cart controller
 *
 * @author Mark Guinn <mark@adaircreative.com>
 * @date 04.07.2014
 * @package shop
 * @subpackage ajax
 */
class ShoppingCartAjax extends Extension
{

    /**
     * @param SS_HTTPRequest $request
     * @param AjaxHTTPResponse $response
     * @param Buyable $product [optional]
     * @param int $quantity [optional]
     */
    public function updateAddResponse(&$request, &$response, $product=null, $quantity=1)
    {
        if ($request->isAjax()) {
            if (!$response) {
                $response = $this->owner->getAjaxResponse();
            }

            // Because ShoppingCart::current() calculates the order once and
            // then remembers the total, and that was called BEFORE the product
            // was added, we need to recalculate again here. Under non-ajax
            // requests the redirect eliminates the need for this but under
            // ajax the total lags behind the subtotal without this.
            ShoppingCart::curr()->calculate();

            $this->setupRenderContexts($response, $product);
            $response->pushRegion('SideCart', $this->owner);
            $response->triggerEvent('cartadd');
            $response->triggerEvent('cartchange', array(
                'action'    => 'add',
                'id'        => $product->ID,
                'quantity'  => $quantity,
            ));

            $this->triggerStatusMessage($response);
        }
    }


    /**
     * @param SS_HTTPRequest $request
     * @param AjaxHTTPResponse $response
     * @param Buyable $product [optional]
     * @param int $quantity [optional]
     */
    public function updateRemoveResponse(&$request, &$response, $product=null, $quantity=1)
    {
        if ($request->isAjax()) {
            if (!$response) {
                $response = $this->owner->getAjaxResponse();
            }

            // Because ShoppingCart::current() calculates the order once and
            // then remembers the total, and that was called BEFORE the product
            // was added, we need to recalculate again here. Under non-ajax
            // requests the redirect eliminates the need for this but under
            // ajax the total lags behind the subtotal without this.
            $order = ShoppingCart::curr();
            $order->calculate();

            $this->setupRenderContexts($response, $product);
            $response->pushRegion('SideCart', $this->owner);
            $response->pushRegion('CartFormAjax', $this->owner, array('Editable' => true));
            $response->triggerEvent('cartremove');
            $response->triggerEvent('cartchange', array(
                'action'    => 'remove',
                'id'        => $product->ID,
                'quantity'  => $quantity,
            ));

            $this->triggerStatusMessage($response);

            // This allows clientside scripts to redirect away from the cart/checkout pages if desired
            if (!$order || !$order->Items()->exists()) {
                $response->triggerEvent('cartempty');
            }
        }
    }


    /**
     * @param SS_HTTPRequest $request
     * @param AjaxHTTPResponse $response
     * @param Buyable $product [optional]
     */
    public function updateRemoveAllResponse(&$request, &$response, $product=null)
    {
        if ($request->isAjax()) {
            if (!$response) {
                $response = $this->owner->getAjaxResponse();
            }

            // Because ShoppingCart::current() calculates the order once and
            // then remembers the total, and that was called BEFORE the product
            // was added, we need to recalculate again here. Under non-ajax
            // requests the redirect eliminates the need for this but under
            // ajax the total lags behind the subtotal without this.
            $order = ShoppingCart::curr();
            $order->calculate();

            $this->setupRenderContexts($response, $product);
            $response->pushRegion('SideCart', $this->owner);
            $response->pushRegion('CartFormAjax', $this->owner, array('Editable' => true));
            $response->triggerEvent('cartremove');
            $response->triggerEvent('cartchange', array(
                'action'    => 'removeall',
                'id'        => $product,
                'quantity'  => 0,
            ));

            $this->triggerStatusMessage($response);

            // This allows clientside scripts to redirect away from the cart/checkout pages if desired
            if (!$order || !$order->Items()->exists()) {
                $response->triggerEvent('cartempty');
            }
        }
    }


    /**
     * @param SS_HTTPRequest $request
     * @param AjaxHTTPResponse $response
     * @param Buyable $product [optional]
     * @param int $quantity [optional]
     */
    public function updateSetQuantityResponse(&$request, &$response, $product=null, $quantity=1)
    {
        if ($request->isAjax()) {
            if (!$response) {
                $response = $this->owner->getAjaxResponse();
            }

            // Because ShoppingCart::current() calculates the order once and
            // then remembers the total, and that was called BEFORE the product
            // was added, we need to recalculate again here. Under non-ajax
            // requests the redirect eliminates the need for this but under
            // ajax the total lags behind the subtotal without this.
            $order = ShoppingCart::curr();
            $order->calculate();

            $this->setupRenderContexts($response, $product);
            $response->pushRegion('SideCart', $this->owner);
            $response->triggerEvent('cartquantity');
            $response->triggerEvent('cartchange', array(
                'action'    => 'setquantity',
                'id'        => $product->ID,
                'quantity'  => $quantity,
            ));

            $this->triggerStatusMessage($response);

            // This allows clientside scripts to redirect away from the cart/checkout pages if desired
            if (!$order || !$order->Items()->exists()) {
                $response->triggerEvent('cartempty');
            }
        }
    }


    /**
     * @param SS_HTTPRequest $request
     * @param AjaxHTTPResponse $response
     */
    public function updateClearResponse(&$request, &$response)
    {
        if ($request->isAjax()) {
            if (!$response) {
                $response = $this->owner->getAjaxResponse();
            }

            // Because ShoppingCart::current() calculates the order once and
            // then remembers the total, and that was called BEFORE the product
            // was added, we need to recalculate again here. Under non-ajax
            // requests the redirect eliminates the need for this but under
            // ajax the total lags behind the subtotal without this.
            ShoppingCart::curr()->calculate();

            $this->setupRenderContexts($response);
            $response->pushRegion('SideCart', $this->owner);
            $response->triggerEvent('cartempty'); // this is triggered any time the cart has no items in it
            $response->triggerEvent('cartchange', array(
                'action'    => 'clear',
            ));

            $this->triggerStatusMessage($response);
        }
    }


    /**
     * Adds the ajax class to the VariationForm
     */
    public function updateVariationForm()
    {
        $this->owner->addExtraClass('ajax');
    }


    /**
     * @param SS_HTTPRequest $request
     * @param AjaxHTTPResponse $response
     * @param Buyable $variation [optional]
     * @param int $quantity [optional]
     * @param VariationForm $form [optional]
     */
    public function updateVariationFormResponse(&$request, &$response, $variation=null, $quantity=1, $form=null)
    {
        if ($request->isAjax()) {
            if (!$response) {
                $response = $this->owner->getAjaxResponse();
            }

            // Because ShoppingCart::current() calculates the order once and
            // then remembers the total, and that was called BEFORE the product
            // was added, we need to recalculate again here. Under non-ajax
            // requests the redirect eliminates the need for this but under
            // ajax the total lags behind the subtotal without this.
            ShoppingCart::curr()->calculate();

            $this->setupRenderContexts($response, $variation);
            $response->addRenderContext('FORM', $form);
            $response->pushRegion('SideCart', $this->owner);
            $response->triggerEvent('cartadd');
            $response->triggerEvent('cartchange', array(
                'action'    => 'add',
                'id'        => $variation->ID,
                'quantity'  => $quantity,
            ));

            $this->triggerStatusMessage($response, $form);
        }
    }


    /**
     * Adds the ajax class to the AddProductForm
     */
    public function updateAddProductForm()
    {
        $this->owner->addExtraClass('ajax');
    }


    /**
     * @param SS_HTTPRequest $request
     * @param AjaxHTTPResponse $response
     * @param Buyable $buyable [optional]
     * @param int $quantity [optional]
     * @param AddProductForm $form [optional]
     */
    public function updateAddProductFormResponse(&$request, &$response, $buyable=null, $quantity=1, $form=null)
    {
        if ($request->isAjax()) {
            if (!$response) {
                $response = $this->owner->getAjaxResponse();
            }

            // Because ShoppingCart::current() calculates the order once and
            // then remembers the total, and that was called BEFORE the product
            // was added, we need to recalculate again here. Under non-ajax
            // requests the redirect eliminates the need for this but under
            // ajax the total lags behind the subtotal without this.
            ShoppingCart::curr()->calculate();

            $this->setupRenderContexts($response, $buyable);
            $response->addRenderContext('FORM', $form);
            $response->pushRegion('SideCart', $this->owner);
            $response->triggerEvent('cartadd');
            $response->triggerEvent('cartchange', array(
                'action'    => 'add',
                'id'        => $buyable->ID,
                'quantity'  => $quantity,
            ));

            $this->triggerStatusMessage($response, $form);
        }
    }


    /**
     * Adds the ajax class to the CartForm
     */
    public function updateCartForm(&$form, $cart)
    {
        $form->addExtraClass('ajax');
        $form->setAttribute('data-ajax-region', 'CartFormAjax');
    }


    /**
     * @param SS_HTTPRequest $request
     * @param AjaxHTTPResponse $response
     * @param AddProductForm $form [optional]
     */
    public function updateCartFormResponse(&$request, &$response, $form=null)
    {
        if ($request->isAjax()) {
            if (!$response) {
                $response = $this->owner->getAjaxResponse();
            }

            // Because ShoppingCart::current() calculates the order once and
            // then remembers the total, and that was called BEFORE the product
            // was added, we need to recalculate again here. Under non-ajax
            // requests the redirect eliminates the need for this but under
            // ajax the total lags behind the subtotal without this.
            ShoppingCart::curr()->calculate();

            $this->setupRenderContexts($response);
            $this->triggerStatusMessage($response, $form);

            $response->pushRegion('CartFormAjax', $this->owner, array('Editable' => true));
            $response->pushRegion('SideCart', $this->owner);
        }
    }


    /**
     * Adds some standard render contexts for pulled regions.
     *
     * @param AjaxHTTPResponse $response
     * @param Buyable $buyable [optional]
     */
    protected function setupRenderContexts(AjaxHTTPResponse $response, $buyable=null)
    {
        if ($this->owner->hasMethod('Cart')) {
            $cart = $this->owner->Cart();
            if ($cart instanceof ViewableData) {
                $response->addRenderContext('CART', $this->owner->Cart());
            }
        }

        if ($buyable) {
            $response->addRenderContext('BUYABLE', $buyable);

            // this could be a Product or ProductVariation (or something else)
            // but we want a render target available for the product specifically
            // for rendering ProductGroupItem in a category or search view
            if ($buyable instanceof Product) {
                $response->addRenderContext('PRODUCT', $buyable);
            } elseif ($buyable->hasMethod('Product')) {
                $response->addRenderContext('PRODUCT', $buyable->Product());
            }
        }
    }

    /**
     * Add status message to the response (only if `show_ajax_messages` config is set)
     * @param AjaxHTTPResponse $response
     * @param Form|null $form the form instance
     */
    protected function triggerStatusMessage($response, $form = null)
    {
        if (!self::config()->show_ajax_messages) {
            return;
        }

        $message = '';
        $type = '';
        if ($this->owner->cart && $this->owner->cart instanceof ShoppingCart) {
            $message = $this->owner->cart->getMessage();
            $type = $this->owner->cart->getMessageType();
            $this->owner->cart->clearMessage();
        }

        if ($form) {
            // if the message was not previously set via cart, get the message from $form
            if (empty($message)) {
                $message = $form->Message();
                $type = $form->MessageType();
            }

            $form->clearMessage();
        }

        if (!empty($message)) {
            $response->triggerEvent('statusmessage', array(
                'content'   => $message,
                'type'      => $type
            ));
        }
    }

    /**
     * Helper for getting static shop config.
     * The 'config' static function isn't avaialbe on Extensions.
     * @return Config_ForClass configuration object
     */
    public static function config()
    {
        return new Config_ForClass("ShoppingCartAjax");
    }
}
