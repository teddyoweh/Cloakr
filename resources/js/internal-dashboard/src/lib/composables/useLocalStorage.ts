import { ref, watch } from 'vue';


export function useLocalStorage<T>(key: string, defaultValue: T) {
    const storedValue = localStorage.getItem(key);

    let parsedValue: T;
    try {
      parsedValue = storedValue ? JSON.parse(storedValue) : defaultValue;
    } catch (error) {
      console.error('Error parsing localStorage data', error);
      parsedValue = defaultValue;
    }

    const data = ref<T>(parsedValue);

    watch(data, (newValue) => {
      try {
        localStorage.setItem(key, JSON.stringify(newValue));
      } catch (error) {
        console.error('Error saving to localStorage', error);
      }
    }, { deep: true });

    return data;
  }