<template>
  <tr>
    <td>{{task.id}}</td>
    <td>{{task.title}}</td>
    <td>{{priorityCapitalized}}</td>
    <td><button class="btn btn-danger w-100 btn-lg"
      @click="remove"
    >Remove</button></td>
  </tr>
</template>

<script setup>
import { computed, defineEmits, defineProps } from 'vue';

const props = defineProps(['task']);
const emit = defineEmits(['delete']);

const priorityCapitalized = computed(() => {
  if (!props.task.priority) return '';
  return props.task.priority.charAt(0).toUpperCase() + props.task.priority.slice(1);
});

function remove() {
  emit('delete', props.task.id);
}
</script>
