<template>
  <tr>
    <td></td>
    <td>
      <input
        type="text"
        id="task"
        class="form-control"
        v-model.trim="task.title"
        :class="{'is-invalid': errors.title}"
        :aria-invalid="!!errors.title"
        ref="taskInput"
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
        :aria-invalid="!!errors.priority"
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
        @click="$emit('store')"
        class="btn btn-primary w-100 btn-lg"
        :disabled="loading || Object.keys(errors).length > 0"
      >
        <span v-if="loading" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
        <span v-else>Add</span>
      </button>
    </td>
  </tr>
</template>

<script setup>
import { toRefs } from 'vue';
const props = defineProps({
  task: { type: Object, required: true },
  errors: { type: Object, required: true },
  loading: { type: Boolean, required: true },
  taskInput: { type: Object, required: true }
});
const { task, errors, loading, taskInput } = toRefs(props);
</script>
