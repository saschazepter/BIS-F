<script>
import NotificationEntry from "./NotificationEntry.vue";
import {useNotificationsStore} from "../../stores/notifications";

export default {
  components: {NotificationEntry},
  setup() {
    const store = useNotificationsStore();
    return {store};
  },
  methods: {
    toggleAllRead() {
      this.store.toggleAllRead().then(() => {
        notyf.success(this.$t("notifications.readAll.success"));
      });
    }
  }
}
</script>

<template>
  <div
      id="notifications-loading"
      class="text-center text-muted"
      v-if="store.loading"
      role="status"
      aria-live="polite"
  >
    <div class="skeleton-wrap" aria-hidden="true">
      <div class="skeleton-row"></div>
      <div class="skeleton-row"></div>
      <div class="skeleton-row"></div>
    </div>
  </div>

  <div
      id="notifications-list"
      v-else-if="store.notifications.length"
      role="list"
  >
    <NotificationEntry
        v-for="(item, index) in store.notifications"
        v-bind="item"
        :key="item.id"
        @toggleRead="store.toggleRead(item, index)"
    />
  </div>

  <div class="text-center text-muted notifications-empty" v-else>
    <i class="fa-solid fa-envelope fs-1" aria-hidden="true"></i>
    <p class="fs-5 m-0">{{ $t("notifications.empty") }}</p>
    <small class="d-block mt-1">{{ $t("notifications.empty.hint") }}</small>
  </div>
</template>

<style scoped lang="scss">
@import "../../../sass/variables";

.row {
  background-color: white;
  padding: 1rem 0;
  border-bottom: 0.5rem solid $body-bg;
  margin: 0;
}

.notifications-empty {
  padding: 2rem 0;
}

.skeleton-wrap {
  padding: 1rem;
}

.skeleton-row {
  height: 56px;
  border-radius: .5rem;
  background: linear-gradient(90deg, rgba(0, 0, 0, .06) 25%, rgba(0, 0, 0, .12) 37%, rgba(0, 0, 0, .06) 63%);
  background-size: 400% 100%;
  animation: shimmer 1.25s ease-in-out infinite;

  & + .skeleton-row {
    margin-top: .75rem;
  }
}

@keyframes shimmer {
  0% {
    background-position: 100% 0;
  }
  100% {
    background-position: 0 0;
  }
}

.dark {
  .row {
    background-color: $dm-base-5;
    border-bottom-color: $dm-base-5;
  }

  .skeleton-row {
    background: linear-gradient(90deg, rgba(255, 255, 255, .06) 25%, rgba(255, 255, 255, .12) 37%, rgba(255, 255, 255, .06) 63%);
  }
}
</style>
