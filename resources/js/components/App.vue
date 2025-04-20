<template>
  <div>
    <table class="table">
      <thead>
        <tr>
          <th>Id</th>
          <th>Task title</th>
          <th>Priority</th>
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
        <tr>
          <td></td>
          <td>
            <input
              type="text"
              id="task"
              class="form-control"
              v-model="task.title"
              :class="{'is-invalid': errors.title}"
            >
            <div v-if="errors.title" class="invalid-feedback">
              {{ errors.title }}
            </div>
          </td>
          <td>
            <select
              id="select"
              class="form-control"
              v-model="task.priority"
              :class="{'is-invalid': errors.priority}"
            >
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
            </select>
            <div v-if="errors.priority" class="invalid-feedback">
              {{ errors.priority }}
            </div>
          </td>
          <td>
            <button
              @click="store"
              class="btn btn-primary btn-block"
              :disabled="!task.title.trim() || !task.priority"
            >Add</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import axios from 'axios';
import { onMounted, reactive, ref } from 'vue';
import TaskComponent from "./Task.vue";

// State
const tasks = ref([]);
const task = reactive({ title: "", priority: "low" });
const errors = reactive({});

// Lifecycle hooks
onMounted(() => {
  getTasks();
});

// Methods
function getTasks() {
  axios
    .get("/api/tasks/")
    .then(({ data }) => data.forEach(item => tasks.value.push(item)));
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

function store() {
  if (!validateForm()) {
    return;
  }

  // Add detailed logging
  console.log('Sending task data:', JSON.stringify(task));

  axios.post("/api/tasks", task)
    .then(response => {
      console.log('Success response:', response.data);
      tasks.value.push(response.data);
      setTimeout(() => window.location.reload(), 100);
      task.title = "";
      task.priority = "low";
      Object.keys(errors).forEach(key => delete errors[key]);
    })
    .catch(error => {
      console.error('Error creating task:', error);
      console.error('Error response data:', error.response ? error.response.data : 'No response data');
      console.error('Error request config:', error.config);

      // Handle validation errors from the server
      if (error.response && error.response.status === 422 && error.response.data.errors) {
        console.error('Validation errors:', error.response.data.errors);
        Object.assign(errors, error.response.data.errors);
      } else {
        alert('Error creating task: ' + (error.response && error.response.data.message ? error.response.data.message : 'Unknown error'));
      }
    });
}

function remove(id) {
  axios.delete("/api/tasks/" + id)
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
</style>
