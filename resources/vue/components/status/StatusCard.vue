<template>
  <div class="card status mb-3">

    <div class="card-body row">
      <div class="col-2 image-box pe-0 d-none d-lg-flex">
        <a href="http://localhost:8000/@Gertrud123">
          <img loading="lazy"
               decoding="async"
               :src="status.userDetails.profilePicture"
               :alt="status.userDetails.username">
        </a>
      </div>

      <div class="col ps-0">
        <ul class="timeline">
          <li>
            <i class="trwl-bulletpoint" aria-hidden="true"></i>
            <span class="text-trwl float-end">
              <span data-mdb-toggle="tooltip" data-mdb-original-title="Planmäßige Zeit">
                {{ formatTime(status.train.origin.departure) }}
              </span>
            </span>

            <a href="http://localhost:8000/stationboard..."
               class="text-trwl clearfix">
              {{ status.train.origin.name }}
            </a>

            <p class="train-status text-muted">
              <span>
                <img class="product-icon"
                     src="http://localhost:8000/img/subway.svg"
                     alt="subway">
                {{ status.train.lineName }}
                <small>({{ status.train.journeyNumber }})</small>
              </span>
              <span class="ps-2">
                <i class="fa fa-route d-inline" aria-hidden="true"></i>
                <div v-if="status.train.distance < 1000">
                  {{ status.train.distance }}<small>m</small>
                </div>
                <div v-else>
                  {{ (status.train.distance / 1000).toFixed(0) }}<small>km</small>
                </div>
              </span>
              <span class="ps-2">
                <i class="fa fa-stopwatch d-inline" aria-hidden="true"></i>
                {{ formatDuration(status.train.duration) }}
              </span>
            </p>
          </li>
          <li>
            <i class="trwl-bulletpoint" aria-hidden="true"></i>
            <span class="text-trwl float-end">
              <span data-mdb-toggle="tooltip" data-mdb-original-title="Planmäßige Zeit">
                {{ formatTime(status.train.destination.arrival) }}
              </span>
            </span>
            <a href="http://localhost:8000/stationboard?stationId=47&amp;stationName=Rinteln"
               class="text-trwl clearfix">
              Rinteln
            </a>
          </li>
        </ul>
      </div>
    </div>
    <div class="progress">
      <div class="progress-bar progress-time " role="progressbar"
           style="width: 3283.14%;"
           data-valuenow="1735947197"
           data-valuemin="1735760058" data-valuemax="1735765758"></div>
    </div>
    <div class="card-footer text-muted interaction px-3 px-md-4">
      <ul class="list-inline float-end">
        <li class="like-text list-inline-item me-0">
          <a href="#" class="like far fa-star" data-trwl-status-id="1"></a>
        </li>
        <li class="like-text list-inline-item">
          <span class="likeCount pl-1" :class="status.likes <= 0 ? 'd-none' : ''">
            {{ status.likes }}
          </span>
        </li>
        <li class="like-text list-inline-item">
          <i class="fas fa-globe-americas visibility-icon text-small" aria-hidden="true" data-mdb-toggle="tooltip"
             data-mdb-placement="top" aria-label="Öffentlich" data-mdb-original-title="Öffentlich"></i>
        </li>
        <li class="like-text list-inline-item">
          <div class="dropdown">
            <a href="#" data-mdb-toggle="dropdown" aria-expanded="false">
              &nbsp;
              <i class="fa fa-ellipsis-vertical" aria-hidden="true"></i>
              &nbsp;
            </a>
            <ul class="dropdown-menu">
              <li>
                <button class="dropdown-item trwl-share" type="button"
                        data-trwl-share-url="http://localhost:8000/status/1"
                        data-trwl-share-text="Ich bin gerade in sz 13 nach Rinteln! #NowTräwelling ">
                  <div class="dropdown-icon-suspense">
                    <i class="fas fa-share" aria-hidden="true"></i>
                  </div>
                  Teilen
                </button>
              </li>
              <li>
                <button class="dropdown-item edit" type="button" data-trwl-status-id="1">
                  <div class="dropdown-icon-suspense">
                    <i class="fas fa-edit" aria-hidden="true"></i>
                  </div>
                  Bearbeiten
                </button>
              </li>
              <li>
                <button class="dropdown-item delete" type="button" data-mdb-toggle="modal"
                        data-mdb-target="#modal-status-delete"
                        onclick="document.querySelector('#modal-status-delete input[name=\'statusId\']').value = '1';">
                  <div class="dropdown-icon-suspense">
                    <i class="fas fa-trash" aria-hidden="true"></i>
                  </div>
                  Löschen
                </button>
              </li>
              <li>
                <hr class="dropdown-divider">
              </li>
              <li>
                <a href="http://localhost:8000/admin/status/edit?statusId=1" class="dropdown-item">
                  <div class="dropdown-icon-suspense">
                    <i class="fas fa-tools" aria-hidden="true"></i>
                  </div>
                  Admin-Interface
                </a>
              </li>
            </ul>
          </div>
        </li>
      </ul>

      <ul class="list-inline">
        <li id="avatar-small-1" class="d-lg-none list-inline-item">
          <a href="http://localhost:8000/@Gertrud123">
            <img src="http://localhost:8000/uploads/avatars/stock_424.png" class="profile-image" alt="Profilbild">
          </a>
        </li>
        <li class="list-inline-item">
          <a href="http://localhost:8000/@Gertrud123">
            Du
          </a>
          um
          <a href="http://localhost:8000/status/1">
            {{ formatTime(status.createdAt) }}
          </a>
        </li>
      </ul>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    status: {
      type: Object,
      required: true,
    },
  },
  methods: {
    formatTime(timestamp) {
      return new Date(timestamp).toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});
    },
    formatDate(date) {
      return new Date(date).toLocaleString();
    },
    formatDuration(duration) {
      const hours = Math.floor(duration / 60);
      const minutes = duration % 60;
      return `${hours}h ${minutes}m`;
    },
  },
};
</script>

<style scoped>
.train-status {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
</style>
