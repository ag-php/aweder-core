import { shallowMount } from '@vue/test-utils';
import AddCategory from '@/js/components/admin/inventory/AddCategory';

describe('AddCategory', () => {
  it('displays the available contents', () => {
    const wrapper = shallowMount(AddCategory);

    expect(wrapper.find('.inventory__add-category').text()).toContain('Add category');
  });

  it('triggers the edit inventory modal to open', () => {
    const wrapper = shallowMount(AddCategory);

    wrapper.find('.inventory__add-category').trigger('click');

    expect(wrapper.vm.isActive).toBe(true);
  });
});
