import { shallowMount, createLocalVue } from '@vue/test-utils';
import storeConfig from '@/js/store/config';
import MiniCart from '@/js/components/store/MiniCart';
import Vuex from 'vuex';
import productsWithoutVariant from './mocks/cart/productsWithoutVariant';
import productsWithVariant from './mocks/cart/productsWithVariant';
import '@/js/filters/Currency';
import '@/js/filters/Capitalize';

const localVue = createLocalVue();

localVue.use(require('vue-moment'));

localVue.use(Vuex);

describe('MiniCart', () => {
  const propsData = {
    merchant: { delivery_cost: 599 },
  };

  it('is inactive when the cart is empty', () => {
    const wrapper = shallowMount(MiniCart, {
      store: new Vuex.Store(storeConfig),
      localVue,
    });

    expect(wrapper.find('.cart--empty').exists()).toBe(true);
  });

  it('shows cart items', async () => {
    storeConfig.modules.cart.state = productsWithoutVariant;

    const wrapper = shallowMount(MiniCart, {
      propsData,
      store: new Vuex.Store(storeConfig),
      localVue,
    });

    expect(wrapper.find('.cart__line').text()).toContain('Nachos,Cheese & Chilli');
    expect(wrapper.find('.cart__line').text()).toContain('£5.00');
    expect(wrapper.find('.cart__option-title').text()).toContain('Option Group');
    expect(wrapper.find('.cart__option-item').text()).toContain('Option 1');
    expect(wrapper.find('.cart__option-item').text()).toContain('£4.99');
  });

  it('shows item variant', async () => {
    storeConfig.modules.cart.state = productsWithVariant;

    const wrapper = shallowMount(MiniCart, {
      propsData,
      store: new Vuex.Store(storeConfig),
      localVue,
    });

    expect(wrapper.find('.cart__title').text()).toContain('Variant - Nachos,Cheese & Chilli');
    expect(wrapper.find('.cart__price').text()).toContain('£11.00');
  });

  it('shows the totals', async () => {
    storeConfig.modules.cart.state = productsWithoutVariant;

    const wrapper = shallowMount(MiniCart, {
      propsData,
      store: new Vuex.Store(storeConfig),
      localVue,
    });

    expect(wrapper.find('.subtotal').text()).toContain('£9.99');
    expect(wrapper.find('.total').text()).toContain('£15.98');
  });

  it('increases the total when incrementing a product', async () => {
    storeConfig.modules.cart.state = productsWithoutVariant;

    const wrapper = shallowMount(MiniCart, {
      propsData,
      store: new Vuex.Store(storeConfig),
      localVue,
    });

    expect(wrapper.find('.subtotal').text()).toContain('£9.99');
    expect(wrapper.find('.total').text()).toContain('£15.98');

    wrapper.find('.increment__type--up').trigger('click');

    await wrapper.vm.$nextTick();

    expect(wrapper.find('.subtotal .cart__price').text()).toContain('£19.98');
    expect(wrapper.find('.total').text()).toContain('£25.97');
  });

  it('removes the line item when quantity drops to zero', async () => {
    storeConfig.modules.cart.state = productsWithoutVariant;

    const wrapper = shallowMount(MiniCart, {
      propsData,
      store: new Vuex.Store(storeConfig),
      localVue,
    });

    expect(wrapper.find('.cart__title').exists()).toBe(true);

    wrapper.find('.increment__type--down').trigger('click');

    await wrapper.vm.$nextTick();

    expect(wrapper.find('.cart__title').exists()).toBe(false);
  });
});
