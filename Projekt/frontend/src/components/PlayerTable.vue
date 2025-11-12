<template>
  <table class="table table-light">
    <thead class="align-baseline">
      <tr>
        <th>Remove</th>
        <th>
          <span>Name</span>
          <button type="button" class="btn btn-secondary btn-sm ms-3" @click="sortByName">
            Sort by Name
          </button>
        </th>
        <th>Add Points</th>
        <th>Deduct Points</th>
        <th>
          <span>Points</span>
          <button type="button" class="btn btn-secondary btn-sm ms-3" @click="sortByPoints">
            Sort by Points
          </button>
        </th>
      </tr>
    </thead>
    <tbody>
      <tr v-for="player in filteredPlayers" :key="player.id">
        <td class="col-md-1">
          <button type="button" class="btn btn-danger" @click="remove(player)">X</button>
        </td>
        <td class="col-md-5">
          <button type="button" class="btn btn" @click="togglePlayerDetails(player)">
            <template v-if="searchQuery && searchQuery.length > 0">
              <span v-html="highlightSearchQuery(player.name)"></span>
            </template>
            <template v-else>
              {{ player.name }}
            </template>
          </button>
          <!-- Player Details Row -->
          <PlayerDetails v-if="showDetails.includes(player.id)" :player="player" />
        </td>
        <td class="col-md-1">
          <button type="button" class="btn btn-success" @click="addPoint(player)">+</button>
        </td>
        <td class="col-md-1">
          <button type="button" class="btn btn-warning" @click="subtractPoint(player)">-</button>
        </td>
        <td class="col-md-2">{{ player.points }}</td>
      </tr>
    </tbody>
  </table>
</template>

<script>
import PlayerDetails from './PlayerDetails.vue';

export default {
  props: {
    filteredPlayers: {
      type: Array,
      required: true
    },
    searchQuery: {
      type: String,
      required: true
    },
    showDetails: {
      type: Array,
      required: true
    }
  },
  methods: {
    remove(player) {
      // Perform the logic to remove the player
      // For example, emit an event to the parent component to handle the removal
      this.$emit('remove-player', player);
    },
    addPoint(player) {
      // Perform the logic to add points to the player
      // For example, emit an event to the parent component to handle the points addition
      this.$emit('add-points', player);
    },
    subtractPoint(player) {
      // Perform the logic to subtract points from the player
      // For example, emit an event to the parent component to handle the points subtraction
      this.$emit('subtract-points', player);
    },
    togglePlayerDetails(player) {
      // Toggle the display of player details
      // For example, emit an event to the parent component to handle the details toggling
      this.$emit('toggle-details', player);
    },
    sortByName() {
      // Perform the logic to sort players by name
      // For example, emit an event to the parent component to handle the sorting
      this.$emit('sort-by-name');
    },
    sortByPoints() {
      // Perform the logic to sort players by points
      // For example, emit an event to the parent component to handle the sorting
      this.$emit('sort-by-points');
    },
    highlightSearchQuery(name) {
      // Perform the logic to highlight the search query in player names
      // For example, you can use a computed property to handle the highlighting
      const query = this.searchQuery.toLowerCase();
      const index = name.toLowerCase().indexOf(query);
      if (index > -1) {
        const highlighted = name.substring(0, index) +
          '<mark>' + name.substring(index, index + query.length) + '</mark>' +
          name.substring(index + query.length);
        return highlighted;
      }
      return name;
    }
  },
  components: {
    PlayerDetails
  }
};
</script>