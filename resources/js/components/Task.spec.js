import { describe, it, expect, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import Task from './Task.vue';

describe('Task.vue', () => {
  const task = { id: 1, title: 'Test Task', priority: 'medium' };

  it('renders task props correctly', () => {
    const wrapper = mount(Task, {
      props: { task }
    });
    expect(wrapper.text()).toContain('Test Task');
    expect(wrapper.text()).toContain('Medium');
    expect(wrapper.text()).toContain('1');
  });

  it('emits delete event with task id when Remove button is clicked', async () => {
    const wrapper = mount(Task, {
      props: { task }
    });
    await wrapper.find('button').trigger('click');
    expect(wrapper.emitted().delete).toBeTruthy();
    expect(wrapper.emitted().delete[0]).toEqual([1]);
  });
});
