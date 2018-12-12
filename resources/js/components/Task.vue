<template>
  <tr>
    <td>{{task.id}}</td>
    <td>{{task.title}}</td>
    <td>{{task.priority}} </td>
    <td><button
        class="btn btn-danger btn-block"
        @click="remove"
      >Remove</button></td>
  </tr>
</template>

<script>
export default {
  data() {
    return {};
  },
  props: ["task"],
  methods: {
    remove() {
      this.$emit("delete", this.task.id);
    }
  }
};
</script>

<style>
</style>
