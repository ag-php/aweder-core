import { mount } from '@vue/test-utils';
import InventoryItem from '@/js/components/store/InventoryItem';

describe('InventoryItem', () => {
  it('displays the available contents', () => {
    const wrapper = mount(InventoryItem, {
      propsData: {
        title: 'Item title',
        description: 'Item description',
        price: '5.00',
      },
    });

    expect(wrapper.find('.inventory__title').text()).toContain('Item title');

    expect(wrapper.find('.inventory__description').text()).toContain('Item description');

    expect(wrapper.find('.inventory__price').text()).toContain('£5.00');
  });

  it('fires an added event on adding the item', () => {
    const wrapper = mount(InventoryItem, {
      propsData: {
        itemId: 'test-id',
      },
    });

    wrapper.find('.inventory__button').trigger('click');

    expect(wrapper.emitted().added[0][0]).toBe('test-id');
  });
});
