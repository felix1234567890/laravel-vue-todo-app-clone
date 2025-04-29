<template>
  <div class="container mt-5">
    <table class="table mt-4">
      <thead>
        <tr>
          <th>Id</th>
          <th><label for="task">Task title</label></th>
          <th><label for="select">Priority</label></th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <TaskComponent
          v-for="taskItem in tasks"
          :key="taskItem.id"
          :task="taskItem"
          @delete="remove"
        />
        <TaskForm
          :task="task"
          :errors="errors"
          :loading="loading"
          :taskInput="taskInput"
          @store="store"
        />
      </tbody>
    </table>
  </div>
</template>

<script setup>
import axios from 'axios';
import { onMounted, reactive, ref, nextTick } from 'vue';
import TaskComponent from "./Task.vue";
import TaskForm from "./TaskForm.vue";

// API endpoint constants
const API_TASKS = "/api/tasks";

// State
const tasks = ref([]);
const task = reactive({ title: "", priority: "low" });
const errors = reactive({});
const loading = ref(false);
const taskInput = ref(null);

// Lifecycle hooks
onMounted(() => {
  getTasks();
});

// Methods
async function getTasks() {
  try {
    const { data } = await axios.get(`${API_TASKS}/`);
    tasks.value = data;
  } catch (error) {
    console.error('Failed to load tasks:', error);
  }
}

function validateForm() {
  Object.keys(errors).forEach(key => delete errors[key]);

  if (!task.title.trim()) {
    errors.title = 'Task title is required';
  }
  if (!task.priority) {
    errors.priority = 'Priority is required';
  }

  // Ensure priority is lowercase
  if (task.priority) {
    task.priority = task.priority.toLowerCase();
  }

  return Object.keys(errors).length === 0;
}

async function store() {
  if (!validateForm()) {
    return;
  }
  loading.value = true;

  try {
    // Add detailed logging
    console.log('Sending task data:', JSON.stringify(task));
    const response = await axios.post(API_TASKS, task);
    console.log('Success response:', response.data);
    tasks.value.push(response.data);
    task.title = "";
    task.priority = "low";
    Object.keys(errors).forEach(key => delete errors[key]);
    // Focus input for faster entry
    await nextTick();
    if (taskInput.value) taskInput.value.focus();
  } catch (error) {
    console.error('Error creating task:', error);
    if (error.response && error.response.data) {
      Object.assign(errors, error.response.data.errors || {});
    } else {
      errors.title = 'Failed to add task.';
    }
  } finally {
    loading.value = false;
  }
}

function remove(id) {
  axios.delete(`${API_TASKS}/${id}`)
    .then(() => {
      const index = tasks.value.findIndex(task => task.id === id);
      if (index !== -1) {
        tasks.value.splice(index, 1);
      }
    })
    .catch(error => {
      console.error('Error deleting task:', error.response ? error.response.data : error.message);
      alert('Error deleting task: ' + (error.response && error.response.data.message ? error.response.data.message : 'Unknown error'));
    });
}
</script>

<style>
.spinner-border { margin-right: 4px; }
.table {
  margin-bottom: 0;
}
tr > td, tr > th {
  vertical-align: middle;
}
.container {
  max-width: 900px;
}
</style>
