<script setup lang="ts">
import ButtonGeneric from '@/components/button/button-generic.vue'
import { nextTick, onMounted, ref } from 'vue'
import ButtonReaction from '@/components/button/button-reaction.vue'

const expanded = ref<boolean>(false)
const overflowStart = ref<boolean>(false)
const overflowEnd = ref<boolean>(false)
const listRef = ref<HTMLElement>()

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
  nextTick(() => updateOverflow())
})

function toggleExpanded() {
  expanded.value = !expanded.value
  emit('onexpanded', expanded.value)
}

function updateOverflow() {
  const el = listRef.value
  if (el) {
    overflowStart.value = el.scrollLeft > 0
    overflowEnd.value = el.scrollLeft + el.clientWidth < el.scrollWidth
  }
}

function onOpenMenu() {
  // Open post menu
}
</script>

<template>
  <div class="card">
    <div class="header">
      <div class="avatar">
        <img
          src="https://gravatar.com/avatar/98d1d36a926a2d31165672799fb86e97dd07c79c07256e7e3d612c9b87fc3e6f?s=200?url"
        />
      </div>
      <div class="username-date">
        <div class="username">@rebecca01</div>
        <div class="date">2 ore fa</div>
      </div>
      <div class="button">
        <button-generic
          icon="menu-h"
          variant="primary"
          :disabled-hover-effect="true"
          :small="true"
          @action="onOpenMenu"
        ></button-generic>
      </div>
    </div>
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
      <div class="image">
        <img src="https://emoticolor.org/cdn/images/02d1209a-e571-44de-943b-6dd6e170b37b.jpg?url" />
      </div>
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
        <div class="list" ref="listRef" @scroll="updateOverflow">
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
    gap: var(--spacing-8);

    .avatar {
      width: 50px;
      height: 50px;
      border-radius: var(--border-radius);
      overflow: hidden;

      img {
        width: 100%;
        height: 100%;
        object-fit: cover;
      }
    }
    .username-date {
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
      gap: var(--spacing-8);
      color: var(--primary);
      height: auto;

      .username {
        font: var(--font-subtitle);
      }
      .date {
        font: var(--font-small);
      }
    }

    .button {
      display: flex;
      align-items: start;
      justify-content: center;
    }
  }
  .color-bar {
    height: 10px;
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
      img {
        width: 100%;
        height: 180px;
        display: block;
        object-fit: cover;
      }
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
        gap: var(--spacing-4);

        width: auto;
        overflow-x: auto;

        scrollbar-width: none;
        &::-webkit-scrollbar {
          display: none;
        }

        position: absolute;
        left: 0;
        right: 0;

        z-index: 1;
      }
    }
  }
}
</style>
