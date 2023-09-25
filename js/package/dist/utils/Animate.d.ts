import { Types } from '../common/types';
export declare namespace Animate {
    const shouldCollapse: (element: HTMLElement) => boolean | 0;
    const hide: (element: HTMLElement, options: Types.Options) => void;
    const show: (element: HTMLElement, options: Types.ShowOptions) => void;
}
