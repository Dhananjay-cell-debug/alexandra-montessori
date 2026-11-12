import { useSyncExternalStore } from 'react';
const subscribe = (listener) => {
  window.addEventListener('ldrn-workspace-pages', listener);
  return () => window.removeEventListener('ldrn-workspace-pages', listener);
};
const snapshot = () => window.ldrnWorkspace?.pages ?? null;
export function useWorkspacePages() {
  return useSyncExternalStore(subscribe, snapshot, () => null);
}
