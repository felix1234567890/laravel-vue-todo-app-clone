import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import TaskForm from './TaskForm.vue';

const factory = (props = {}) => {
  return mount(TaskForm, {
    props: {
      task: { title: '', priority: 'low', ...(props.task || {}) },
      errors: props.errors || {},
      loading: props.loading || false,
      taskInput: { current: null },
      ...props,
    },
  });
};

describe('TaskForm.vue', () => {
  it('renders input and select with correct initial values', () => {
    const wrapper = factory({ task: { title: 'Test Task', priority: 'medium' } });
    const input = wrapper.find('input#task');
    const select = wrapper.find('select#select');
    expect(input.element.value).toBe('Test Task');
    expect(select.element.value).toBe('medium');
  });

  it('shows validation errors when present', () => {
    const wrapper = factory({ errors: { title: 'Title error', priority: 'Priority error' } });
    expect(wrapper.html()).toContain('Title error');
    expect(wrapper.html()).toContain('Priority error');
    expect(wrapper.find('input#task').classes()).toContain('is-invalid');
    expect(wrapper.find('select#select').classes()).toContain('is-invalid');
  });

  it('emits store event when Add button is clicked', async () => {
    const wrapper = factory();
    await wrapper.find('button').trigger('click');
    expect(wrapper.emitted()).toHaveProperty('store');
  });

  it('disables button when loading or errors exist', async () => {
    const wrapper1 = factory({ loading: true });
    expect(wrapper1.find('button').element.disabled).toBe(true);
    const wrapper2 = factory({ errors: { title: 'Required' } });
    expect(wrapper2.find('button').element.disabled).toBe(true);
  });
});
