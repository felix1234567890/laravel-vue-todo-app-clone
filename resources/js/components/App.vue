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
        <task-component
          v-for="task in tasks"
          :key="task.id"
          :task="task"
          @delete="remove"
        ></task-component>
        <tr>
          <td></td>
          <td><input
              type="text"
              id="task"
              class="form-control"
              v-model="task.title"
            ></td>
          <td><select
              id="select"
              class="form-control"
              v-model="task.priority"
            >
              <option>Low</option>
              <option>Medium</option>
              <option>High</option>
            </select></td>
          <td><button
              @click="store"
              class="btn btn-primary btn-block"
            >Add</button></td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script>
import TaskComponent from "./Task";
export default {
  components: {
    TaskComponent
  },
  data() {
    return {
      tasks: [],
      task: { title: "", priority: "" }
    };
  },
  created() {
    this.getTasks();
  },
  methods: {
    getTasks() {
      window.axios
        .get("/api/tasks/")
        .then(({ data }) => data.forEach(item => this.tasks.push(item)));
    },
    store() {
      console.log(this.task);
      window.axios.post("/api/tasks", this.task).then(task => {
        this.tasks.push(task);
        setTimeout(window.location.reload(), 100);
        this.task.title = "";
        this.task.priority = "";
      });
    },
    remove(id) {
      window.axios.delete("/api/tasks/" + id).then(() => {
        let index = this.tasks.findIndex(task => task.id === id);
        this.tasks.splice(index, 1);
      });
    }
  }
};
</script>

<style>
</style>
