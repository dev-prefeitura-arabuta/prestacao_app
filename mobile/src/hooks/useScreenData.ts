import { useCallback, useEffect, useState } from 'react';
import { Alert } from 'react-native';
import { errorMessage } from '../utils/format';

/**
 * Carrega os dados de uma tela ao montar e expõe `reload` para o pull-to-refresh.
 * Erros viram um alerta "Aviso", como no comportamento original.
 */
export function useScreenData<T>(loader: () => Promise<T>, initial: T) {
  const [data, setData] = useState<T>(initial);
  const [loading, setLoading] = useState(false);

  const reload = useCallback(async () => {
    setLoading(true);
    try {
      setData(await loader());
    } catch (e: unknown) {
      Alert.alert('Aviso', errorMessage(e, 'Erro ao conectar na API.'));
    } finally {
      setLoading(false);
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  useEffect(() => {
    reload();
  }, [reload]);

  return { data, loading, reload };
}
