<script>
export default {
  props: {
    id: String,
    type: String,
    leadFormatted: String,
    lead: String,
    noticeFormatted: String,
    notice: String,
    link: String,
    data: Object,
    readAt: String,
    createdAt: String,
    createdAtForHumans: String
  },
  emits: ['toggle-read'],
  methods: {
    toggleUnread() {
      this.$emit('toggle-read');
    }
  },
  computed: {
    internalLink() {
      if (this.link) return this.link;
      return "#";
    },
    linkRel() {
      try {
        const u = new URL(this.internalLink, window.location.origin);
        const isExternal = u.origin !== window.location.origin;
        return isExternal ? 'noopener noreferrer' : null;
      } catch {
        return null;
      }
    },
    icon() {
      switch (this.type) {
        case 'EventSuggestionProcessed':
          return 'fa-regular fa-calendar';
        case 'FollowRequestApproved':
          return 'fas fa-user-plus';
        case 'FollowRequestIssued':
          return 'fas fa-user-plus';
        case 'MastodonNotSent':
        case 'InvalidMastodonServer':
          return 'fas fa-exclamation-triangle';
        case 'StatusLiked':
          return 'fas fa-heart';
        case 'UserFollowed':
          return 'fas fa-user-friends';
        case 'UserJoinedConnection':
          return 'fa fa-train';
        case 'UserMentioned':
          return 'fas fa-at';
        case 'PersonalDataExportedNotification':
          return 'fas fa-download';
        default:
          return 'far fa-envelope';
      }
    },
    warnType() {
      switch (this.type) {
        case 'MastodonNotSent':
        case 'InvalidMastodonServer':
          return 'warning';
        default:
          return 'neutral';
      }
    },
    read() {
      return this.readAt ?? false;
    },
    toggleTitle() {
      return this.read
          ? this.$t?.('notifications.mark-as-unread') ?? 'Mark as unread'
          : this.$t?.('notifications.mark-as-read') ?? 'Mark as read';
    },
    exactTimestamp() {
      return this.createdAt || '';
    }
  }
}
</script>

<template>
  <div
      class="row notification"
      :class="[warnType, { unread: !read }]"
      role="listitem"
  >
    <a
        class="col-1 col-sm-1 align-left lead"
        :href="internalLink"
        :rel="linkRel"
        :aria-label="lead || 'Open notification'"
    >
      <i :class="icon" aria-hidden="true"></i>
    </a>

    <a
        class="col-7 col-sm-8 align-middle text-decoration-none"
        :href="internalLink"
        :rel="linkRel"
    >
      <p class="lead" v-html="leadFormatted"></p>
      <span v-html="noticeFormatted ?? ''"></span>
    </a>

    <div class="col col-sm-3 text-end">
      <button
          type="button"
          class="interact toggleReadState"
          @click="toggleUnread"
          :aria-pressed="!!read"
          :title="toggleTitle"
      >
        <span class="visually-hidden">{{ toggleTitle }}</span>
        <span aria-hidden="true">
          <i class="far" :class="{'fa-envelope': !read, 'fa-envelope-open': read}"></i>
        </span>
      </button>
      <div class="text-muted timestamp" :title="exactTimestamp">{{ createdAtForHumans }}</div>
    </div>
  </div>
</template>

<style scoped lang="scss">
@import "../../../sass/variables";

div {
  font-size: var(--bs-body-font-size);
  line-height: var(--bs-body-line-height);
}

.notification {
  a {
    color: $text-color;
  }
}

.unread {
  position: relative;

  &::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background-color: $blue;
    opacity: .9;
    border-radius: 0 4px 4px 0;
  }

  &.warning {
    background-color: lighten($bahnrot, 40%);
  }

  &.neutral {
    background-color: lighten($blue, 40%);
  }
}

.col-1 i,
.interact {
  font-weight: 700;
  line-height: 1;
  color: $dark;
  text-shadow: 0 1px 0 #fff;
  padding: 0;
  background-color: transparent;
  border: 0;
  -webkit-appearance: none;
  -moz-appearance: none;
  font-size: 1.25rem;

  &:focus-visible {
    outline: 2px solid currentColor;
    outline-offset: 2px;
    border-radius: .25rem;
  }
}

p.lead {
  margin-bottom: 0.5rem;

  i {
    padding-right: 0.5rem;
  }
}

a ::v-deep(b) {
  font-weight: bold;
}

.timestamp {
  white-space: nowrap;
}

.dark { // class .dark is on <html>
  .fas, .far, .fa {
    filter: invert(1);
  }

  a {
    color: $dm-body;
  }

  .unread {
    &::before {
      background-color: $blue;
      opacity: .8;
    }

    &.warning {
      background-color: mix($dm-base, $bahnrot, 78%);
    }

    &.neutral {
      background-color: mix($dm-base, $blue, 78%);
    }
  }
}
</style>
