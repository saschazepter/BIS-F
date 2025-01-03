<template>
  <div
      v-for="status in statuses"
      :key="status.id"
  >
    <status-card :status="status"></status-card>
  </div>
</template>

<script>
// TODO: switch between private / global dashboard
// TODO: show (own) future statuses
// TODO: headings (date)
// TODO: pagination
// TODO: empty dashboard
// TODO: rewrite endpoints: rename to some better key names + cursor pagination

import StatusCard from './StatusCard.vue';
import axios from 'axios';

export default {
  components: {
    StatusCard
  },
  data() {
    return {
      statuses: []
    };
  },
  async mounted() {
    console.log('Dashboard mounted');
    const response = await axios.get('/api/v1/dashboard');
    this.statuses = response.data.data;
    console.log(this.statuses);
  }
};
</script>

<style scoped>
</style>
