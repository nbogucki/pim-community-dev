import React, {FC} from 'react';
import {CategoryTreeModel} from '../../../models';
import {Node} from './Node';
import {CategoryTreeProvider} from '../../providers';

type Props = {
  root: CategoryTreeModel | null;
  rootLabel: string;
  sortable?: boolean; // @todo find a better name: editable?
  followCategory?: (category: CategoryTreeModel) => void;
  addCategory?: (categoryId: number) => void; // @todo define arguments that we really need
  deleteCategory?: (categoryId: number) => void; // @todo define arguments that we really need
  // @todo define onCategoryMoved action
};

const CategoryTree: FC<Props> = ({root, rootLabel, sortable = false, ...rest}) => {
  /* @todo[PLG-94] show loading feedback when tree is null? */
  if (root === null) {
    return <>Tree {rootLabel}</>;
  }

  return (
    <CategoryTreeProvider root={root}>
      <Node id={root.id} label={root.label} sortable={sortable} {...rest} />
    </CategoryTreeProvider>
  );
};

export {CategoryTree};
