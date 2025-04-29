import { describe, it, expect, vi, beforeEach } from 'vitest';
import { mount, flushPromises } from '@vue/test-utils';
import App from './App.vue';
import TaskComponent from './Task.vue';
import axios from 'axios';

vi.mock('axios');

describe('App.vue', () => {
  let wrapper;
  const mockTasks = [
    { id: 1, title: 'First Task', priority: 'low' },
    { id: 2, title: 'Second Task', priority: 'high' },
  ];

  beforeEach(async () => {
    axios.get.mockResolvedValue({ data: mockTasks });
    axios.post.mockResolvedValue({ data: { id: 3, title: 'New Task', priority: 'medium' } });
    axios.delete.mockResolvedValue({});
    wrapper = mount(App, {
      global: {
        mocks: {},
      },
      attachTo: document.body,
    });
    await flushPromises();
  });

  it('renders tasks from API', () => {
    expect(wrapper.html()).toContain('First Task');
    expect(wrapper.html()).toContain('Second Task');
  });

  it('adds a new task on store', async () => {
    const input = wrapper.find('input#task');
    await input.setValue('New Task');
    const select = wrapper.find('select#select');
    await select.setValue('medium');
    await wrapper.findComponent({ name: 'TaskForm' }).find('button').trigger('click');
    await flushPromises();
    expect(wrapper.html()).toContain('New Task');
  });

  it('shows validation error if title is empty', async () => {
    const input = wrapper.find('input#task');
    await input.setValue('');
    await wrapper.findComponent({ name: 'TaskForm' }).find('button').trigger('click');
    await flushPromises();
    expect(wrapper.html()).toContain('Task title is required');
  });

  it('removes a task when delete is called', async () => {
    // Use the imported TaskComponent directly
    const taskComponent = wrapper.findAllComponents(TaskComponent)[0];
    expect(taskComponent.exists()).toBe(true);
    await taskComponent.vm.$emit('delete', 1);
    await flushPromises();
    expect(wrapper.html()).not.toContain('First Task');
  });
});
