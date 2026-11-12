import { useSyncExternalStore } from 'react';
const subscribe = (listener) => {
  window.addEventListener('am-workspace-pages', listener);
  return () => window.removeEventListener('am-workspace-pages', listener);
};
const snapshot = () => window.amWorkspace?.pages ?? null;
export function useWorkspacePages() {
  return useSyncExternalStore(subscribe, snapshot, () => null);
}
