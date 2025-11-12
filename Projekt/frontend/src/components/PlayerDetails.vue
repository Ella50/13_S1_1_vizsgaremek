<template>
  <div>
    <form>
      <div class="form-group row">
        <label for="colFormLabelSm" class="col-sm-2 col-form-label col-form-label-sm">Name</label>
        <div class="col-sm-10">
          <input type="text" class="form-control form-control-sm text-info" :value="player.name" readonly>
        </div>
      </div>
      <div class="form-group row">
        <label for="colFormLabelSm" class="col-sm-2 col-form-label col-form-label-sm">Age</label>
        <div class="col-sm-10">
          <input type="text" class="form-control form-control-sm text-info" :value="player.age" readonly>
        </div>
      </div>
      <div class="form-group row">
        <label for="colFormLabelSm" class="col-sm-2 col-form-label col-form-label-sm">Points</label>
        <div class="col-sm-10">
          <input type="text" class="form-control form-control-sm text-info" :value="player.points" readonly>
        </div>
      </div>
      <div class="form-group row">
        <label for="colFormLabelSm" class="col-sm-2 col-form-label col-form-label-sm">Address</label>
        <div class="col-sm-10">
          <input type="text" class="form-control form-control-sm text-info" :value="player.address" readonly>
        </div>
      </div>
    </form>
  </div>
</template>

<script>
export default {
  props: {
    player: {
      type: Object,
      required: true
    }
  }
};
</script>