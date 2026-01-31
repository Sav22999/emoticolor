<script setup lang="ts">
import ButtonGeneric from '@/components/button/button-generic.vue'
import { onMounted, ref } from 'vue'
import ButtonReaction from '@/components/button/button-reaction.vue'

const expanded = ref<boolean>(false)
const overflowStart = ref<boolean>(false)
const overflowEnd = ref<boolean>(false)

const props = withDefaults(
  defineProps<{
    id?: string
    ownPost?: boolean
    expandedByDefault?: boolean
  }>(),
  {
    id: undefined,
    ownPost: false,
    expandedByDefault: false,
  },
)

const emit = defineEmits<{
  (e: 'onexpanded', value: boolean): void
}>()

onMounted(() => {
  if (props.expandedByDefault) {
    expanded.value = true
  }
})

function toggleExpanded() {
  expanded.value = !expanded.value
  emit('onexpanded', expanded.value)
}
</script>

<template>
  <div class="card">
    <div class="header"></div>
    <div class="color-bar"></div>
    <div class="content-emotion">@rebecca01 stava provando tristezza</div>
    <div class="content">
      Oggi mi sento come un cielo coperto: non piove, ma manca il sole. È una tristezza silenziosa,
      quella che non si vede ma pesa. Mi fermo un attimo, respiro, e ricordo a me stessa che va bene
      così. Anche i giorni grigi hanno qualcosa da insegnare.
    </div>
    <button-generic
      :text="expanded ? 'Nascondi dettagli' : 'Mostra dettagli'"
      icon-position="end"
      :icon="expanded ? 'chevron-up' : 'chevron-down'"
      :no-border-radius="true"
      :small="true"
      @action="toggleExpanded"
    />
    <div class="content-expanded" v-if="expanded">
      <div class="variables"></div>
      <div class="image"></div>
    </div>
    <div class="reactions">
      <div class="reaction-button">
        <button-generic
          variant="primary"
          icon="reactions"
          :small="true"
          :disabled-hover-effect="true"
        />
      </div>
      <div class="all-reactions">
        <div class="shadow-in-start" v-if="overflowStart"></div>
        <div class="shadow-in-end" v-if="overflowEnd"></div>
        <div class="list">
          <button-reaction reaction="1f3af" />
          <button-reaction reaction="1f622" />
          <button-reaction reaction="1f635-200d-1f4ab" />
          <button-reaction reaction="1f60d" />
          <button-reaction reaction="1f44d" />
          <button-reaction reaction="1f61f" />
          <button-reaction reaction="1f621" />
          <button-reaction reaction="1f3af" />
          <button-reaction reaction="1f622" />
          <button-reaction reaction="1f635-200d-1f4ab" />
          <button-reaction reaction="1f60d" />
          <button-reaction reaction="1f44d" />
          <button-reaction reaction="1f61f" />
          <button-reaction reaction="1f621" />
          <button-reaction reaction="1f3af" />
          <button-reaction reaction="1f622" />
          <button-reaction reaction="1f635-200d-1f4ab" />
          <button-reaction reaction="1f60d" />
          <button-reaction reaction="1f44d" />
          <button-reaction reaction="1f61f" />
          <button-reaction reaction="1f621" />
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped lang="scss">
.card {
  background-color: var(--color-blue-10);
  border-radius: var(--border-radius);
  overflow: hidden;
  width: 100%;

  display: flex;
  flex-direction: column;

  .header {
    padding: var(--padding-8);
    display: flex;
    flex-direction: row;

    .title {
    }
    .button {
    }
  }
  .color-bar {
    height: 4px;
    background-color: var(--color-blue-50);
  }
  .content-emotion {
    padding: var(--padding-16);
    font: var(--font-paragraph);
  }
  .content {
    padding: var(--padding-16);
    font: var(--font-paragraph);
  }
  .content-expanded {
    background-color: var(--color-blue-20);

    display: flex;
    flex-direction: column;

    .variables {
      padding: var(--padding-16);
    }
    .image {
    }
  }
  .reactions {
    padding: var(--padding);
    display: flex;
    flex-direction: row;
    gap: var(--spacing-16);

    .reaction-button {
    }

    .all-reactions {
      flex: 1;

      display: flex;
      flex-direction: row;
      gap: var(--spacing-4);

      position: relative;
      width: 100%;

      .shadow-in-start {
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 40px;
        background: linear-gradient(to right, var(--color-blue-10), var(--no-color));
        pointer-events: none;
        z-index: 2;
      }
      .shadow-in-end {
        position: absolute;
        right: 0;
        top: 0;
        bottom: 0;
        width: 40px;
        background: linear-gradient(to left, var(--color-blue-10), var(--no-color));
        pointer-events: none;
        z-index: 2;
      }

      .list {
        display: flex;
        flex-direction: row;
        gap: var(--spacing-8);

        width: auto;
        overflow-x: auto;

        position: absolute;
        left: 0;
        right: 0;

        z-index: 1;
      }
    }
  }
}
</style>
